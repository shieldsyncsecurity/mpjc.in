<?php
/**
 * Simple RSS proxy for CA Mohit Jain & Associates website
 * ---------------------------------------------------------
 * Fetches an RSS feed server-side and returns it with permissive CORS
 * so the front-end JS can render live news without a backend service.
 *
 * Upload this file to the website root (same folder as index.html).
 * Works on any standard PHP host (Hostinger, GoDaddy, cPanel, etc.).
 *
 * Caches each feed for 30 minutes to reduce upstream calls.
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Content-Type: application/rss+xml; charset=utf-8');
header('Cache-Control: public, max-age=1800');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

$url = isset($_GET['url']) ? $_GET['url'] : '';

// Allow-list of upstream hosts to prevent open-proxy abuse
$allowedHosts = [
    'news.google.com',
    'feeds.feedburner.com',
    'taxguru.in',
    'www.icai.org',
    'icai.org',
    'economictimes.indiatimes.com',
    'www.thehindubusinessline.com',
    'www.business-standard.com',
];

$host = parse_url($url, PHP_URL_HOST);
if (!$url || !$host || !in_array($host, $allowedHosts, true)) {
    http_response_code(400);
    echo '<?xml version="1.0"?><error>Invalid or disallowed feed URL</error>';
    exit;
}

$cacheDir = sys_get_temp_dir();
$cacheFile = $cacheDir . DIRECTORY_SEPARATOR . 'mj_rss_' . md5($url) . '.xml';
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 1800) {
    readfile($cacheFile);
    exit;
}

$ctx = stream_context_create([
    'http' => [
        'timeout' => 10,
        'header'  => "User-Agent: Mozilla/5.0 (compatible; MJRSS/1.0)\r\n",
        'follow_location' => 1,
    ],
    'https' => [
        'timeout' => 10,
        'header'  => "User-Agent: Mozilla/5.0 (compatible; MJRSS/1.0)\r\n",
        'follow_location' => 1,
    ],
]);

$body = @file_get_contents($url, false, $ctx);
if ($body === false || strlen($body) < 50) {
    http_response_code(502);
    echo '<?xml version="1.0"?><error>Upstream fetch failed</error>';
    exit;
}

@file_put_contents($cacheFile, $body);
echo $body;
