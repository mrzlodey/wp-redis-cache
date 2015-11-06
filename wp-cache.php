<?php
$redis_host = '127.0.0.1';
$redis_port = '6379';
$redis_sock = '/var/run/redis/redis.sock';
$redis_unix = true;
$cheche_timeout = 3600;
$debug_show = true;
$debug_msgs;

// Page load time
$start = microtime();

if (class_exists('Redis')) {
    // PhpRedis PECL extension
    $redis = new Redis();
    if ($redis_unix) { $redis->connect($redis_sock); }
    else { $redis->connect($redis_host, $redis_port); }
}
else {
    // Predis PHP client library
    require( dirname(__FILE__) . '/predis/Autoloader.php');
    Predis\Autoloader::register();
    if ($redis_unix) { $redis = new Predis\Client('unix://'. $redis_sock); }
    else { $redis = new Predis\Client('tcp://'. $redis_host .':'. $redis_port); }
}

// Get page URL
parse_str($_SERVER['QUERY_STRING'], $ar);
$url = strtok($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], '?') . http_build_query(array_diff_key($ar,array('flush'=>"")));
$cache_key = md5($url);

// If logged in to WordPress
if (preg_match("/wordpress_logged_in/", var_export($_COOKIE, true))) {
    //Load WordPress index.php
    require( dirname(__FILE__) . '/index.php');
    $debug_msgs .= "<!-- logged_cache_off -->\n";
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
        echo $redis->get($cache_key);
        $debug_msgs .= "<!-- cache_key: $cache_key -->\n";
    }
    // Cached page not exists
    else {
        ob_start();
        require( dirname(__FILE__) . '/index.php');
        // Adding page to cache
        $html = ob_get_contents();
        ob_end_clean();
        echo $html;
        $redis->setex($cache_key, $cheche_timeout, $html);
        $debug_msgs .= "<!-- adding_cache -->\n";
    }
}

function getMicroTime($time) {
    list($usec, $sec) = explode(" ", $time);
    return ((float) $usec + (float) $sec);
}

if ($debug_show) {
    $end  = microtime();
    $time = (@getMicroTime($end) - @getMicroTime($start));
    echo "\n<!-- Page generated in ". round($time, 5) ." seconds. -->\n";
    echo $debug_msgs;
}
