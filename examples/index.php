<?php

//
// new style
//
use \YPEarlyCache\Factory;

$manager = Factory::getManager('path\to\config.php');
if (!$manager->flushCacheIfAble()) {

	//
    // TODO: generate $content, $headers, $responseCode here
	//

    $manager->setCache($content, $headers, $responseCode);

}