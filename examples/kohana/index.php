<?php

//
// new style
//
use \YPEarlyCache\Factory;

$manager = Factory::getManager(Factory::PLATFORM_KOHANA, 'path\to\config.php', $_GET, $_SERVER, $_COOKIE);
if (!$manager->flushCacheIfAble()) {

    // !!! Rename original index.php to index_kohana.php and then place this index.php into web app folder
    // to split implementation put kohana code to separate file
    // @see index_kohana.php in the same directory
    require_once('index_kohana.php');

    // generate $content, $headers, $responseCode here
    // response must be generated in index_kohana.php file
    $text = $response->body();
    $headers = $response->headers()->getArrayCopy();
    $status = $response->status();

    // save cache
    $manager->setCache($content, $headers, $responseCode);

}
