<?php

// @TODO: improve the router with the following router: https://github.com/wp-cli/server-command

/**
 * .htaccess replacement router for php server
 */

// Allow Wordpress to XHR from other domains
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');

$root = $_SERVER['DOCUMENT_ROOT'];

chdir($root);

$path = '/' . ltrim(parse_url($_SERVER['REQUEST_URI'])['path'], '/');

set_include_path(get_include_path() . ':' . __DIR__);

if(file_exists($root . $path)) {

    if(is_dir($root . $path) && substr($path, strlen($path) - 1, 1) !== '/') {
        $path = rtrim($path,'/') . '/index.php';
    }
    if(strpos($path, '.php') === false) {
        return false;
    } else {
        chdir(dirname($root . $path));
        require_once $root . $path;
    }

} else {

    if($path == '/beheer' || $path == '/beheer/') {
        // Show link to the correct wp-admin environment
        echo '<h2><a href="http://127.0.0.1.xip.io:8000/wp/wp-admin/index.php">Klik om naar de beheeromgeving te gaan</a></h2>';
        die();
    }

    include_once 'index.php';

}