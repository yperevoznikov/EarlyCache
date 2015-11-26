<?php namespace YPEarlyCache;

use YPEarlyCache\Config\ArrayConfig;

/**
 * 	@covers \YPEarlyCache\Factory
 */
class FactoryTest extends \PHPUnit_Framework_TestCase {

    /**
     * @dataProvider dataProviderTestGetManagerDiffConfigs
     */
    public function testGetManagerDiffConfigs($source){
        $mgr = Factory::getManager($source);
        $this->assertInstanceOf('\YPEarlyCache\Manager', $mgr);
    }

    public function dataProviderTestGetManagerDiffConfigs()
    {
        $configsBase = dirname(__FILE__) . '/data-example/';
        $arrConfig = require($configsBase . 'php-require-config-1.php');
		$objConfig = new ArrayConfig($arrConfig);
        return array(
            array($configsBase . 'php-require-config-1.php'),
            array($configsBase . 'xml-config-1.xml'),
            array($configsBase . 'yaml-config-1.yaml'),
            array($configsBase . 'json-config-1.json'),
            array($objConfig),
			array($arrConfig)
        );
    }

}