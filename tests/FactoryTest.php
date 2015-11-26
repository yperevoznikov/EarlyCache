<?php namespace YPEarlyCache;

/**
 * 	@covers \YPEarlyCache\Factory
 */
class FactoryTest extends \PHPUnit_Framework_TestCase {

    /**
     * @dataProvider dataProviderTestGetManagerDiffConfigs
     */
    public function testGetManagerDiffConfigs($configPath){
        $mgr = Factory::getManager($configPath);
        $this->assertInstanceOf('\YPEarlyCache\Manager', $mgr);
    }

    public function dataProviderTestGetManagerDiffConfigs()
    {
        $configsBase = dirname(__FILE__) . '/data-example/';
        return array(
            array($configsBase . 'php-require-config-1.php'),
            array($configsBase . 'xml-config-1.xml'),
            array($configsBase . 'yaml-config-1.yaml'),
        );
    }

}