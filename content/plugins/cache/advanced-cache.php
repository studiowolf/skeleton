<?php

global $cache_stop;
$cache_stop = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cache_stop = true;
    return false;
}

if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != '') {
    sw_cache_header('stop - query string');
    $cache_stop = true;
    return false;
}

if (defined('SID') && SID != '') {
    $cache_stop = true;
    return false;
}

if (isset($_COOKIE['cache_disable'])) {
    $cache_stop = true;
    return false;
}

if (isset($_SERVER['HTTP_CACHE_CONTROL']) && $_SERVER['HTTP_CACHE_CONTROL'] == 'no-cache') {
    sw_cache_header('stop - no cache header');
    $cache_stop = true;
    return false;
}

if (isset($_SERVER['HTTP_PRAGMA']) && $_SERVER['HTTP_PRAGMA'] == 'no-cache') {
    sw_cache_header('stop - no cache header');
    $cache_stop = true;
    return false;
}


if (0 && isset($_SERVER['HTTP_USER_AGENT'])) {
    if (preg_match('#()#i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
        sw_cache_header('stop - user agent');
        $cache_stop = true;
        return false;
    }
}

if (!empty($_COOKIE)) {
    foreach ($_COOKIE as $n => $v) {
        if (substr($n, 0, 12) == 'wp-postpass_') {
            sw_cache_header('stop - password cookie');
            $cache_stop = true;
            return false;
        }
    }
}

$hc_uri = sw_cache_sanitize_uri($_SERVER['REQUEST_URI']);

$hc_file = WP_CONTENT_DIR . '/cache/' . strtolower($_SERVER['HTTP_HOST']) . $hc_uri . '/index.html';

if (!is_file($hc_file)) {
    sw_cache_header('continue - no file');
    return false;
}

$hc_file_time = filemtime($hc_file);

if (array_key_exists("HTTP_IF_MODIFIED_SINCE", $_SERVER)) {
    $hc_if_modified_since = strtotime(preg_replace('/;.*$/', '', $_SERVER["HTTP_IF_MODIFIED_SINCE"]));
    if ($hc_if_modified_since >= $hc_file_time) {
        header("HTTP/1.0 304 Not Modified");
        flush();
        die();
    }
}

header('Content-Type: text/html;charset=UTF-8');
header('Last-Modified: ' . gmdate("D, d M Y H:i:s", $hc_file_time) . ' GMT');
header('Vary: Accept-Encoding');
header('Cache-Control: must-revalidate');
header('Pragma: no-cache');
header('Content-Length: ' . filesize($hc_file));
sw_cache_header('hit - plain');

echo file_get_contents($hc_file);

flush();
die();

function sw_cache_sanitize_uri($uri) {
    $uri = preg_replace('/[^a-zA-Z0-9\.\/\-_]+/', '_', $uri);
    $uri = preg_replace('/\/+/', '/', $uri);
    $uri = rtrim($uri, '.-_/');
    if (empty($uri) || $uri[0] != '/') {
        $uri = '/' . $uri;
    }
    return rtrim($uri, '/');
}

function sw_cache_header($value) {
    header('X-Cache: ' . $value);
}
