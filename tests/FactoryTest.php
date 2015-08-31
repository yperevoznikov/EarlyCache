<?php

namespace YPEarlyCache;

/**
 * 	@covers \YPEarlyCache\Factory
 */
class FactoryTest extends \PHPUnit_Framework_TestCase {

    public function testGetManager(){
        $configPath = dirname(__FILE__) . '/data-example/kohana-config-1.php';
        $mgr = Factory::getManager(Factory::KOHANA, $configPath, array(), array(), array());
        $this->assertInstanceOf('\YPEarlyCache\Manager', $mgr);
    }

}