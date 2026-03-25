<?php
declare(strict_types=1);

namespace Services;

if (file_exists(dirname(__DIR__, 2) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
    require_once dirname(__DIR__, 2) . '/vendor/kornrunner/keccak/src/Keccak.php';
}

use Core\Database;

class WalletAuthService
{
    // ── Nonce generieren (Challenge für Wallet-Signatur) ──────────────────
    public function generateNonce(string $ipHash): string
    {
        $nonce = bin2hex(random_bytes(32));
        $db    = Database::getInstance();

        // Alte Nonces aufräumen
        $db->exec('DELETE FROM auth_nonces WHERE expires_at < NOW()');

        $db->prepare('
            INSERT INTO auth_nonces (nonce, ip_hash, expires_at)
            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))
        ')->execute([$nonce, $ipHash]);

        return $nonce;
    }

    // ── SIWE Message zusammenbauen ────────────────────────────────────────
    public function buildMessage(string $address, string $nonce): string
    {
        $domain    = 'aidzap.com';
        $chainId   = 1; // Ethereum Mainnet
        $issuedAt  = date('c');
        $uri       = 'https://aidzap.com';

        return "aidzap.com wants you to sign in with your Ethereum account:\n"
             . "{$address}\n\n"
             . "Sign in to aidzap.com – Privacy-first crypto advertising.\n\n"
             . "URI: {$uri}\n"
             . "Version: 1\n"
             . "Chain ID: {$chainId}\n"
             . "Nonce: {$nonce}\n"
             . "Issued At: {$issuedAt}";
    }

    // ── Signatur verifizieren ─────────────────────────────────────────────
    public function verifySignature(string $message, string $signature, string $address): bool
    {
        try {
            // Ethereum Personal Sign Prefix – normalize first, then calculate length
            $message = str_replace("\r\n", "\n", $message);
            $prefix  = "\x19Ethereum Signed Message:\n" . mb_strlen($message, "8bit");
            $msgHashHex = $this->keccak256($prefix . $message);

            // Signatur dekodieren
            $sig = hex2bin(substr(strpos($signature, '0x') === 0 ? substr($signature, 2) : $signature, 0));
            if (strlen($sig) !== 65) return false;

            $r = substr($sig, 0, 32);
            $s = substr($sig, 32, 32);
            $v = ord($sig[64]);

            // v normalisieren
            if ($v >= 27) $v -= 27;
            if ($v !== 0 && $v !== 1) return false;

            // EC Recovery – hash als hex übergeben
            $ec  = new \Elliptic\EC("secp256k1");
            $sig = ["r" => bin2hex($r), "s" => bin2hex($s)];
            $pubKey = $ec->recoverPubKey($msgHashHex, $sig, $v);
            $pubKeyHex = $pubKey->encode("hex");

            // Adresse aus Public Key ableiten (ohne 04 Prefix)
            $pubKeyBin = hex2bin(substr($pubKeyHex, 2));
            $keccak = new \kornrunner\Keccak();
            $addrHash = $keccak->hash($pubKeyBin, 256);
            $recoveredAddress = '0x' . substr($addrHash, 24);

            return strtolower($recoveredAddress) === strtolower($address);
        } catch (\Throwable $e) {
            return false;
        }
    }

    // ── User aus Wallet-Adresse holen oder erstellen ──────────────────────
    public function findOrCreateUser(string $address): array
    {
        $address = strtolower($address);
        $db      = Database::getInstance();

        // Existing user?
        $stmt = $db->prepare('SELECT * FROM users WHERE wallet_address = ? LIMIT 1');
        $stmt->execute([$address]);
        $user = $stmt->fetch();

        if ($user) return ['user' => $user, 'created' => false];

        // Neuen User anlegen (anonym)
        $username = 'wallet_' . substr(str_replace('0x', '', $address), 0, 8);
        $uuid     = $this->uuid();

        // Username eindeutig machen falls nötig
        $count = (int)$db->prepare('SELECT COUNT(*) FROM users WHERE username LIKE ?')
                         ->execute([$username . '%']) ?: 0;
        if ($count > 0) $username .= '_' . mt_rand(100, 999);

        $db->prepare('
            INSERT INTO users (uuid, username, password_hash, role, wallet_address, wallet_chain, status)
            VALUES (?, ?, ?, "both", ?, "ethereum", "active")
        ')->execute([$uuid, $username, '', $address]);

        $userId = (int)$db->lastInsertId();
        $stmt   = $db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        return ['user' => $user, 'created' => true];
    }

    // ── Nonce validieren ──────────────────────────────────────────────────
    public function validateAndConsumeNonce(string $nonce): bool
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('
            SELECT id FROM auth_nonces
            WHERE nonce = ? AND used = 0 AND expires_at > NOW()
            LIMIT 1
        ');
        $stmt->execute([$nonce]);
        $row = $stmt->fetch();

        if (!$row) return false;

        $db->prepare('UPDATE auth_nonces SET used = 1 WHERE id = ?')->execute([$row['id']]);
        return true;
    }

    // ── Keccak256 (vereinfacht via hash) ─────────────────────────────────
    private function keccak256(string $data): string
    {
        return (new \kornrunner\Keccak())->hash($data, 256);
    }

    private function ecRecover(string $hash, string $r, string $s, int $v): ?string
    {
        try {
            $ec = new \Elliptic\EC("secp256k1");
            $sig = ["r" => bin2hex($r), "s" => bin2hex($s)];
            $pubKey = $ec->recoverPubKey($hash, $sig, $v);
            $pubKeyHex = $pubKey->encode("hex");
            // Remove 04 uncompressed prefix
            $pubKeyBin = hex2bin(substr($pubKeyHex, 2));
            return $pubKeyBin;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function uuid(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),
            mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,
            mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));
    }
}
