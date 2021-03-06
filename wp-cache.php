<?php
$redis_host = '127.0.0.1';
$redis_port = '6379';
$redis_sock = '/var/run/redis/redis.sock';
$redis_unix = true;
$cheche_timeout = 24 * 60 * 60;
$debug_show = true;
$debug_msgs = '';

// Page load time
$start = microtime();

if (class_exists('Redis')) {
    // PhpRedis PECL extension
    $redis = new Redis();
    $redis_unix ? $redis->connect($redis_sock) : $redis->connect($redis_host, $redis_port);}
else {
    // Predis PHP client library
    require(dirname(__FILE__) . '/predis/Autoloader.php');
    Predis\Autoloader::register();
    $redis = ($redis_unix ? new Predis\Client('unix://'. $redis_sock) : new Predis\Client('tcp://'. $redis_host .':'. $redis_port));
}

// Get page URL
parse_str($_SERVER['QUERY_STRING'], $vars);
$url = strtok($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], '?') . http_build_query(array_diff_key($vars,array('flush'=>"")));
$cache_key = md5($url);

// If logged in to WordPress
if (preg_match("/wordpress_logged_in/", var_export($_COOKIE, true))) {
    //Load WordPress index.php
    require(dirname(__FILE__) . '/index.php');
    // Flush Radis cache by adding ?flush=true after the URL
    if ($_GET['flush'] == true && $redis->exists($cache_key)) {
        $redis->del($cache_key);
        $debug_msgs .= "<!-- cache_flush -->\n";
    }
}
// If not logged in to WordPress
else {
    // Cached page exists
    if ($redis->exists($cache_key)) {
        if (strpos($url, '/feed/')) header('Content-Type: application/rss+xml; charset=UTF-8');
        echo $redis->get($cache_key);
        $debug_msgs .= "<!-- $cache_key -->\n";
    }
    // Cached page not exists
    else {
        ob_start();
        require(dirname(__FILE__) . '/index.php');
        // Adding page to cache
        $html = ob_get_contents();
        ob_end_clean();
        echo $html;
        $redis->setex($cache_key, $cheche_timeout, $html);
    }
}

function get_microtime($time) {
    list($usec, $sec) = explode(" ", $time);
    return ((float) $usec + (float) $sec);
}

if ($debug_show) {
    $end  = microtime();
    $time = (@get_microtime($end) - @get_microtime($start));
    echo "\n<!-- Page generated in ". round($time, 5) ." seconds. -->\n" . $debug_msgs;
}
