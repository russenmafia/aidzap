/**
 * wallet.js – Unified Web3Modal v3 + MetaMask wallet connector for aidzap.com
 * Uses ESM imports; load via <script type="module">
 */

// ── SIWE helpers ─────────────────────────────────────────────────────────────

async function fetchNonce() {
    const resp = await fetch('/wallet/nonce');
    if (!resp.ok) throw new Error('Failed to fetch nonce');
    const { nonce } = await resp.json();
    return nonce;
}

function buildSiweMessage(address, nonce) {
    return [
        'aidzap.com wants you to sign in with your Ethereum account:',
        address,
        '',
        'Sign in to aidzap.com',
        '',
        'URI: https://aidzap.com',
        'Version: 1',
        'Chain ID: 1',
        `Nonce: ${nonce}`,
        `Issued At: ${new Date().toISOString()}`,
    ].join('\n');
}

// ── Web3Modal initialisation ──────────────────────────────────────────────────

let _modal = null;

export async function initWeb3Modal(projectId) {
    if (!projectId) return null;
    if (_modal) return _modal;

    try {
        const { createWeb3Modal, defaultWagmiConfig } = await import(
            'https://cdn.jsdelivr.net/npm/@web3modal/wagmi@3/dist/index.js'
        );
        const { mainnet } = await import(
            'https://cdn.jsdelivr.net/npm/viem@2/dist/index.js'
        );

        const metadata = {
            name: 'aidzap',
            description: 'Crypto Ad Network',
            url: 'https://aidzap.com',
            icons: ['https://aidzap.com/assets/img/og-image.png'],
        };

        const chains = [mainnet];
        const wagmiConfig = defaultWagmiConfig({ chains, projectId, metadata });
        _modal = createWeb3Modal({ wagmiConfig, projectId, chains });
    } catch (e) {
        console.warn('Web3Modal failed to load, falling back to MetaMask only:', e);
        _modal = null;
    }

    return _modal;
}

// ── Sign a message with the connected provider ────────────────────────────────

export async function signMessage(address, message) {
    if (typeof window.ethereum === 'undefined') {
        throw new Error('No wallet provider found');
    }
    return await window.ethereum.request({
        method: 'personal_sign',
        params: [message, address],
    });
}

// ── Main connect + sign flow ──────────────────────────────────────────────────

/**
 * Opens Web3Modal (or falls back to MetaMask) and completes SIWE signing.
 * Returns { address, signature, nonce, message }
 */
export async function connectWallet(projectId) {
    let address;

    if (typeof window.ethereum !== 'undefined') {
        // MetaMask (or injected provider) is available – use it directly
        const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
        if (!accounts || !accounts[0]) throw new Error('No account returned');
        address = accounts[0];
    } else {
        // No injected provider – open Web3Modal
        const modal = await initWeb3Modal(projectId);
        if (!modal) {
            throw new Error('No wallet provider detected. Please install MetaMask or use a WalletConnect-compatible wallet.');
        }

        await modal.open();

        // Wait for connection
        address = await new Promise((resolve, reject) => {
            const timeout = setTimeout(() => reject(new Error('Wallet connection timed out')), 120_000);
            const unsub = modal.subscribeState((state) => {
                if (state.selectedNetworkId && window.ethereum) {
                    window.ethereum.request({ method: 'eth_accounts' }).then((accs) => {
                        if (accs && accs[0]) {
                            clearTimeout(timeout);
                            unsub();
                            resolve(accs[0]);
                        }
                    });
                }
            });
        });
    }

    const nonce   = await fetchNonce();
    const message = buildSiweMessage(address, nonce);
    const signature = await signMessage(address, message);

    return { address, signature, nonce, message };
}
