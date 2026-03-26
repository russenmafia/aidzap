<?php
/**
 * aads.com Publisher List Scraper
 * Scrapes all publishers sorted by traffic and exports to CSV
 */

$outputFile = __DIR__ . '/aads_publishers_' . date('Y-m-d') . '.csv';
$baseUrl    = 'https://aads.com/network/publishers/';
$delay      = 2; // seconds between requests (be polite)
$maxPages   = 500; // safety limit

$publishers = [];
$page       = 1;
$totalFound = 0;

echo "Starting aads.com publisher scrape...\n";
echo "Output: {$outputFile}\n\n";

// CSV Header
$fp = fopen($outputFile, 'w');
fputcsv($fp, [
    'Rank',
    'Website',
    'URL',
    'Ad Units',
    'Impressions',
    'Impressions_Raw',
    'CPM',
    'CPM_Raw',
    'KYC_Required',
    'Categories',
    'aads_URL',
]);

$rank = 0;

while ($page <= $maxPages) {
    $url = $baseUrl . '?page=' . $page . '&sort=impressions&order=desc';
    
    echo "Fetching page {$page}... ";
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER     => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.5',
            'Accept-Encoding: gzip, deflate, br',
            'Connection: keep-alive',
        ],
        CURLOPT_ENCODING       => '', // auto decompress
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    
    $html = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    
    if ($err || $code !== 200 || empty($html)) {
        echo "FAILED (HTTP {$code}: {$err})\n";
        break;
    }
    
    // Parse HTML
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);
    
    // Find publisher rows - aads uses table rows
    $rows = $xpath->query('//table//tr[td]');
    
    if ($rows->length === 0) {
        // Try alternative selectors
        $rows = $xpath->query('//*[contains(@class,"publisher-row") or contains(@class,"site-row")]');
    }
    
    if ($rows->length === 0) {
        echo "No rows found - checking for end of data or structure change\n";
        
        // Check if there's a "no results" message
        $noResults = $xpath->query('//*[contains(text(),"No publishers") or contains(text(),"no results")]');
        if ($noResults->length > 0) {
            echo "End of publisher list reached.\n";
        } else {
            // Save HTML for debugging
            file_put_contents('/tmp/aads_debug_page' . $page . '.html', $html);
            echo "Saved debug HTML to /tmp/aads_debug_page{$page}.html\n";
        }
        break;
    }
    
    $pageCount = 0;
    foreach ($rows as $row) {
        $cells = $xpath->query('td', $row);
        if ($cells->length < 3) continue;
        
        $rank++;
        $pageCount++;
        
        // Extract data from cells
        $websiteCell     = $cells->item(0);
        $impressionsCell = $cells->item(1) ?? null;
        $cpmCell         = $cells->item(2) ?? null;
        
        // Website name and URL
        $link    = $xpath->query('.//a', $websiteCell)->item(0);
        $website = trim($websiteCell->textContent);
        $website = preg_replace('/\s+/', ' ', $website);
        $website = trim(preg_replace('/KYC required|\d+ ad units?/i', '', $website));
        
        $siteUrl  = '';
        $aadsUrl  = '';
        if ($link) {
            $href = $link->getAttribute('href');
            if (str_starts_with($href, 'http')) {
                $siteUrl = $href;
            } else {
                $aadsUrl = 'https://aads.com' . $href;
            }
        }
        
        // Ad units count
        $adUnitsMatch = [];
        preg_match('/(\d+)\s+ad\s+units?/i', $websiteCell->textContent, $adUnitsMatch);
        $adUnits = $adUnitsMatch[1] ?? '';
        
        // KYC required
        $kycRequired = str_contains($websiteCell->textContent, 'KYC') ? 'Yes' : 'No';
        
        // Impressions
        $impressionsRaw = trim($impressionsCell ? $impressionsCell->textContent : '');
        $impressionsRaw = preg_replace('/\s+/', '', $impressionsRaw);
        $impressionsNum = (int)preg_replace('/[^0-9]/', '', $impressionsRaw);
        
        // CPM
        $cpmRaw = trim($cpmCell ? $cpmCell->textContent : '');
        $cpmRaw = trim(preg_replace('/\s+/', ' ', $cpmRaw));
        
        fputcsv($fp, [
            $rank,
            $website,
            $siteUrl,
            $adUnits,
            $impressionsRaw,
            $impressionsNum,
            $cpmRaw,
            (float)preg_replace('/[^0-9.]/', '', $cpmRaw),
            $kycRequired,
            '', // Categories - not available in list view
            $aadsUrl,
        ]);
        
        $publishers[] = [
            'rank'        => $rank,
            'website'     => $website,
            'impressions' => $impressionsNum,
            'cpm'         => $cpmRaw,
        ];
    }
    
    echo "Found {$pageCount} publishers (total: {$rank})\n";
    
    if ($pageCount === 0) {
        echo "No more data found.\n";
        break;
    }
    
    // Check for next page
    $nextPage = $xpath->query('//a[contains(@href,"page=".($page+1)) or contains(@rel,"next")]');
    $hasNext  = false;
    foreach ($nextPage as $np) {
        $hasNext = true;
        break;
    }
    
    // Also check if we got a full page (assume 20 per page)
    if ($pageCount < 5) {
        echo "Less than 5 results on page - assuming end of list.\n";
        break;
    }
    
    $page++;
    sleep($delay);
}

fclose($fp);

echo "\n========================================\n";
echo "DONE! Total publishers scraped: {$rank}\n";
echo "Output file: {$outputFile}\n";
echo "File size: " . round(filesize($outputFile) / 1024, 1) . " KB\n";
echo "\nTop 10 by Impressions:\n";
usort($publishers, fn($a, $b) => $b['impressions'] - $a['impressions']);
foreach (array_slice($publishers, 0, 10) as $i => $p) {
    printf("%2d. %-40s %15s  CPM: %s\n", 
        $i + 1,
        substr($p['website'], 0, 40),
        number_format($p['impressions']),
        $p['cpm']
    );
}
