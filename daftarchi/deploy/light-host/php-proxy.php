<?php
/**
 * PHP/Apache fallback when the host cannot run gunicorn in public_html.
 *
 * Put this file as index.php in the DOCUMENT ROOT of a SUBDOMAIN
 * (example: pos.webakery.ir → public_html/pos.webakery.ir/index.php).
 * WordPress on webakery.ir stays in public_html/.
 *
 * Do not drop this inside a /daftarchi/ folder on the WordPress domain:
 * the app loads /api and /static from the origin root.
 *
 * Backend is only the URL in proxy-config.php (or DAFTARCHI_BACKEND).
 * Never taken from the query string.
 */
declare(strict_types=1);

$configFile = __DIR__ . '/proxy-config.php';
$config = is_file($configFile) ? require $configFile : array();
if (!is_array($config)) {
    $config = array();
}
$backend = rtrim((string) (getenv('DAFTARCHI_BACKEND') ?: ($config['backend'] ?? 'http://127.0.0.1:8080')), '/');
$timeout = (int) ($config['timeout'] ?? 60);
if ($timeout < 5) {
    $timeout = 5;
}
if ($timeout > 120) {
    $timeout = 120;
}

if (!function_exists('curl_init')) {
    header('Content-Type: text/plain; charset=utf-8', true, 500);
    echo 'curl PHP extension is required for the Daftarchi proxy.';
    exit;
}

$parts = parse_url($backend);
$host = strtolower((string) ($parts['host'] ?? ''));
$scheme = strtolower((string) ($parts['scheme'] ?? ''));
$allowRemote = !empty($config['allow_remote']);
$loopback = ($host === '127.0.0.1' || $host === 'localhost' || $host === '::1');
if ($scheme !== 'http' && $scheme !== 'https') {
    header('Content-Type: text/plain; charset=utf-8', true, 500);
    echo 'Daftarchi proxy backend must be http or https.';
    exit;
}
if ($host === '' || isset($parts['user']) || isset($parts['pass'])) {
    header('Content-Type: text/plain; charset=utf-8', true, 500);
    echo 'Daftarchi proxy backend is invalid.';
    exit;
}
if (!$loopback && !$allowRemote) {
    header('Content-Type: text/plain; charset=utf-8', true, 500);
    echo 'Set allow_remote in proxy-config.php to proxy to a remote VPS.';
    exit;
}

$uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
if ($uri === '' || $uri[0] !== '/') {
    $uri = '/' . ltrim($uri, '/');
}
$target = $backend . $uri;

$curl = curl_init($target);
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$headers = array();
$skip = array('connection', 'keep-alive', 'transfer-encoding', 'te', 'trailer', 'upgrade', 'host', 'content-length');
if (function_exists('getallheaders')) {
    foreach (getallheaders() as $name => $value) {
        if (in_array(strtolower((string) $name), $skip, true)) {
            continue;
        }
        $headers[] = $name . ': ' . $value;
    }
} else {
    if (!empty($_SERVER['HTTP_COOKIE'])) {
        $headers[] = 'Cookie: ' . $_SERVER['HTTP_COOKIE'];
    }
    if (!empty($_SERVER['CONTENT_TYPE'])) {
        $headers[] = 'Content-Type: ' . $_SERVER['CONTENT_TYPE'];
    }
    if (!empty($_SERVER['HTTP_ACCEPT'])) {
        $headers[] = 'Accept: ' . $_SERVER['HTTP_ACCEPT'];
    }
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers[] = 'Authorization: ' . $_SERVER['HTTP_AUTHORIZATION'];
    }
}
$fwd = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
if ($fwd !== '') {
    $headers[] = 'X-Forwarded-For: ' . $fwd;
}
$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$headers[] = 'X-Forwarded-Proto: ' . $proto;
$headers[] = 'X-Forwarded-Host: ' . (string) ($_SERVER['HTTP_HOST'] ?? '');

$body = null;
if ($method !== 'GET' && $method !== 'HEAD') {
    $body = file_get_contents('php://input');
}

curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HEADER, true);
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 8);
curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
if ($body !== null && $body !== false && $body !== '') {
    curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
}
if ($method === 'HEAD') {
    curl_setopt($curl, CURLOPT_NOBODY, true);
}

$raw = curl_exec($curl);
$errno = curl_errno($curl);
$status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
$headerSize = (int) curl_getinfo($curl, CURLINFO_HEADER_SIZE);
curl_close($curl);

if ($raw === false || $errno) {
    header('Content-Type: text/plain; charset=utf-8', true, 502);
    echo 'Daftarchi is not reachable on the light-host backend.';
    exit;
}

$headerBlob = substr($raw, 0, $headerSize);
$payload = substr($raw, $headerSize);
$hop = array('connection', 'keep-alive', 'transfer-encoding', 'te', 'trailer', 'upgrade');
foreach (preg_split("/\r\n/", $headerBlob) as $line) {
    if (stripos($line, 'HTTP/') === 0 || $line === '') {
        continue;
    }
    $colon = strpos($line, ':');
    if ($colon === false) {
        continue;
    }
    $name = trim(substr($line, 0, $colon));
    $value = trim(substr($line, $colon + 1));
    if (in_array(strtolower($name), $hop, true)) {
        continue;
    }
    header($name . ': ' . $value, false);
}
http_response_code($status > 0 ? $status : 502);
echo $payload;
