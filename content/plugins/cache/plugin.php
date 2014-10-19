<?php

/*
  Plugin Name: Cache
  Plugin URI: http://www.github.com
  Description: A extremely basic, non-configurable cache plugin based on Hyper Cache
  Version: 1.0.0
  Author: Tim Sluis
  Author URI: http://www.studiowolf.net
  Disclaimer: Use at your own risk. No warranty expressed or implied is provided.
  Thanks to: satollo (http://www.satollo.net)
 */

if (isset($_GET['cache'])) {
    if ($_GET['cache'] === '0') {
        setcookie('cache_disable', 1, time() + 3600 * 24 * 365, '/');
        $x = strpos($_SERVER['REQUEST_URI'], '?');
        header('Location:' . substr($_SERVER['REQUEST_URI'], 0, $x));
        die();
    }

    if ($_GET['cache'] === '1') {
        setcookie('cache_disable', 1, time() - 3600, '/');
        $x = strpos($_SERVER['REQUEST_URI'], '?');
        header('Location:' . substr($_SERVER['REQUEST_URI'], 0, $x));
        die();
    }
}

new SWCache();

global $cache_stop;

class SWCache {

    var $post_id;
    static $instance;

    function __construct() {
        self::$instance = $this;

        register_activation_hook('cache/plugin.php', array($this, 'hook_activate'));
        register_deactivation_hook('cache/plugin.php', array($this, 'hook_deactivate'));

        add_action('edit_post', array($this, 'hook_edit_post'), 1);
        add_action('transition_post_status', array($this, 'hook_transition_post_status'), 10, 3);
        add_action('comment_post', array($this, 'hook_comment_post'), 1, 2);
        add_action('wp_update_comment_count', array($this, 'hook_wp_update_comment_count'), 1);

        if (!is_admin() && !isset($_COOKIE['cache_disable'])) {
            // The function must exists or the advanced-cache.php has been removed
            add_action('template_redirect', array($this, 'hook_template_redirect'), 0);
        } else {
            add_action('wp_dashboard_setup',  array($this, 'hook_wp_dashboard_setup'));
        }
    }


    function hook_wp_dashboard_setup() {
        wp_add_dashboard_widget('clear_cache_widget', 'Cache ingeschakeld', array($this, 'clear_cache_widget'));

        if($_POST) {
            // Verify nonce
            if(isset($_POST['clear_cache']) && wp_verify_nonce($_POST['clear_cache_nonce'], 'clear-cache-nonce')) {
                add_action('admin_notices', array($this, 'clear_cache_notice'));
                $this->clear_cache();
            }
        }

        if(!defined('WP_CACHE') || !WP_CACHE) {
            add_action('admin_notices', array($this, 'wp_cache_error_notice'));
        }

        if(!is_dir($this->get_folder())) {
            add_action('admin_notices', array($this, 'cache_directory_error_notice'));
        }

        if(get_option('permalink_structure') == '') {
            add_action('admin_notices', array($this, 'cache_permalink_error_notice'));
        }

    }

    function clear_cache_widget() {
        echo '<p>Cache is ingeschakeld op deze website. Klik op \'Cache legen\' om handmatig de cache te legen.</p>';
        echo '<form action="" method="post">';
        submit_button('Cache legen', 'secondary', 'clear_cache');
        wp_nonce_field('clear-cache-nonce', 'clear_cache_nonce');
        echo '</form>';
        return;
    }

    function clear_cache_notice() {
        echo '<div class="updated">';
        echo '<p>De cache is geleegd.</p>';
        echo '</div>';
    }


    function wp_cache_error_notice() {
        echo '<div class="error">';
        echo '<p>Om cache in te schakelen moet de regel:
              <code>define(\'WP_CACHE\', true);</code>
              in de wp-config.php file staan.</p>';
        echo '</div>';
    }

    function cache_directory_error_notice() {
        echo '<div class="error">';
        printf(__('Cache was not able to create or find the %s folder. ', 'sw-cache'),
            '<code>' . $this->get_folder() . '</code>');
        _e('Please create it manually with list, write and read permissions (usually 777).', 'sw-cache');
        echo '</div>';
    }

    function cache_permalink_error_notice() {
        echo '<div class="error"><p>';
        _e('You should choose a different permalink structure. ', 'sw-cache');
        _e('Change it on the <a href="options-permalink.php" target="_blank">permalink panel</a> otherwise Cache cannot work properly.', 'sw-cache');
        echo '</p></div>';
    }

    function hook_activate() {

        @wp_mkdir_p(WP_CONTENT_DIR . '/cache');

        if (is_file(WP_CONTENT_DIR . '/advanced-cache.php')) {
            $this->build_advanced_cache();
            touch(WP_CONTENT_DIR . '/advanced-cache.php');
        }
    }

    function hook_deactivate() {
        // Reset the file without deleting it to avoid to lost manually assigned permissions
        file_put_contents(WP_CONTENT_DIR . '/advanced-cache.php', '');
    }

    function build_advanced_cache() {
        $advanced_cache = file_get_contents(dirname(__FILE__) . '/advanced-cache.php');
        return file_put_contents(WP_CONTENT_DIR . '/advanced-cache.php', $advanced_cache);
    }

    function hook_comment_post($comment_id, $status) {
        if ($status === 1) {
            $comment = get_comment($comment_id);
            $this->hook_edit_post($comment->comment_post_ID);
        }
    }

    function hook_wp_update_comment_count($post_id) {
        if ($this->post_id == $post_id) {
            return;
        }
        $this->hook_edit_post($post_id);
    }

    function hook_edit_post($post_id) {

        if ($this->post_id == $post_id) {
            return;
        }

        if (get_post_status($post_id) != 'publish') {
            return;
        }

        $this->clear_cache();
        return;
    }


    function hook_transition_post_status($new_status, $old_status, $post) {

        if($new_status == $old_status) {
            return;
        }

        if($old_status != 'publish') {
            return;
        }

        $this->clear_cache();
        return;
    }


    function clear_cache() {
        $folder = $this->get_folder();
        $this->remove_dir($folder . '');
    }

    function hook_template_redirect() {
        global $cache_stop;

        if ($cache_stop) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            return;
        }

        if (!empty($_SERVER['QUERY_STRING'])) {
            return;
        }

        if (is_404()) {

            $file = $this->get_folder() . '/' . substr(get_option('home'), strpos(get_option('home'), '://') + 3) . '/404.html';

            if (file_exists($file)) {
                header('Content-Type: text/html;charset=UTF-8');
                // For some reason it seems more performant than readfile...
                header('X-Cache: hit');
                echo file_get_contents($file);
                die();
            }
        }

        if (is_trackback()) {
            return;
        }

        if (defined('SID') && SID != '') {
            return;
        }

        $home_root = parse_url(get_option('home'), PHP_URL_PATH);
        if (substr($_SERVER['REQUEST_URI'], 0, strlen($home_root) + 4) == ($home_root . '/wp-')) {
            return;
        }

        // Compatibility with XML Sitemap 4.x
        if (substr($_SERVER['REQUEST_URI'], 0, strlen($home_root) + 8) == ($home_root . '/sitemap')) {
            return;
        }

        ob_start('sw_cache_callback');
    }

    function post_folder($post_id) {
        $url = get_permalink($post_id);
        $parts = parse_url($url);
        return $parts['host'] . sw_cache_sanitize_uri($parts['path']);
    }

    function remove_dir($dir) {
        //error_log('Removing dir: ' . $dir);
        $dir = trailingslashit($dir);
        $files = glob($dir . '*', GLOB_MARK);
        if (!empty($files)) {
            foreach ($files as &$file) {
                if (substr($file, -1) == DIRECTORY_SEPARATOR)
                    $this->remove_dir($file);
                else {
                    @unlink($file);
                }
            }
        }
        @rmdir($dir);
    }

    function remove_older_than($time) {
        $this->_remove_older_than($time, $this->get_folder() . '/');
    }

    function _remove_older_than($time, $dir) {
        $files = glob($dir . '*', GLOB_MARK);
        if (!empty($files)) {
            foreach ($files as &$file) {
                if (substr($file, -1) == '/')
                    $this->_remove_older_than($time, $file);
                else {
                    //error_log($file . ' ' . ($time-filemtime($file)));
                    if (@filemtime($file) < $time) {
                        //error_log('Removing ' . $file);
                        @unlink($file);
                    }
                }
            }
        }
    }

    function get_folder() {
        return WP_CONTENT_DIR . '/cache';
    }

    function text_to_list($text) {
        $list = array();
        $items = explode("\n", str_replace(array("\n", "\r"), "\n", $text));
        foreach ($items as &$item) {
            $item = trim($item);
            if ($item == '')
                continue;
            $list[] = $item;
        }
        return $list;
    }

}

function sw_cache_callback($buffer) {
    global $cache_stop, $lite_cache;

    if ($cache_stop)
        return $buffer;
    if (strlen($buffer) == 0)
        return '';

    $uri = sw_cache_sanitize_uri($_SERVER['REQUEST_URI']);

    $lc_dir = SWCache::$instance->get_folder() . '/' . strtolower($_SERVER['HTTP_HOST']) . $uri;

    if (is_404()) {
        $lc_file = SWCache::$instance->get_folder() . '/' . strtolower($_SERVER['HTTP_HOST']) . '/404.html';
    } else {
        $lc_file = $lc_dir . '/index.html';

        if (!is_dir($lc_dir)) {
            wp_mkdir_p($lc_dir);
        }
    }

    $buffer = apply_filters('cache_buffer', $buffer);

    file_put_contents($lc_file, $buffer);

    return $buffer;
}

if (!function_exists('sw_cache_sanitize_uri')) {

    function sw_cache_sanitize_uri($uri) {
        $uri = preg_replace('/[^a-zA-Z0-9\.\/\-_]+/', '_', $uri);
        $uri = preg_replace('/\/+/', '/', $uri);
        $uri = rtrim($uri, '.-_/');
        if (empty($uri) || $uri[0] != '/') {
            $uri = '/' . $uri;
        }
        return rtrim($uri, '/');
    }

}
