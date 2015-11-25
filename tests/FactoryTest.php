<?php namespace YPEarlyCache;

/**
 * 	@covers \YPEarlyCache\Factory
 */
class FactoryTest extends \PHPUnit_Framework_TestCase {

    public function testGetManager(){
        $configPath = dirname(__FILE__) . '/data-example/php-require-config-1.php';
        $mgr = Factory::getManager($configPath, Factory::CONFIG_PHP_REQUIRED, array(), array(), array());
        $this->assertInstanceOf('\YPEarlyCache\Manager', $mgr);
    }

}