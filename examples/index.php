<?php

//
// new style
//
use \YPEarlyCache\Factory;

$manager = Factory::getManager(Factory::KOHANA, 'path\to\config.php', $_GET, $_SERVER, $_COOKIE);
if (!$manager->flushCacheIfAble()) {

	//
    // TODO: generate $content, $headers, $responseCode
	//

    $manager->setCache($content, $headers, $responseCode);

}