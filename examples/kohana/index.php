<?php

//
// new style
//
use \YPEarlyCache\Factory;

$manager = Factory::getManager(Factory::PLATFORM_KOHANA, 'path\to\config.php', $_GET, $_SERVER, $_COOKIE);
if (!$manager->flushCacheIfAble()) {

    //
    // generate $content, $memoType, $responseCode here
    //

    // save cache
    $manager->setCache($content, $memoType, $responseCode);

}


//
// old style
//
$enable_early_cache = true;
if (isset($_GET['early_cache']) && 'false' == $_GET['early_cache']) {
    $enable_early_cache = false;
}
if (isset($_COOKIE['authautologin'])) {
    $enable_early_cache = false;
}

require_once("early_cache.php");
$uri = $_SERVER["REQUEST_URI"];
if ($enable_early_cache) {
    if (earlyCacheNeedCache($uri)) {
        $text = earlyCacheGetHtml($uri);

        if (substr_count($text, "Страница не найдена :(")) {
            $text = false;
        }
    }
}

$cacheSeconds = (int)(earlyCacheGetCacheSeconds(earlyCacheGetType($uri)));
if (isset($text) && false !== $text) {
    header("Content-Type: text/html");
    header("Cache-Control: max-age={$cacheSeconds}");
} else {

    // get $text content here

    if ($cacheSeconds && earlyCacheNeedCache($uri)) {
        earlyCacheSetHtml($uri, $text);
    }
}

echo $text;
