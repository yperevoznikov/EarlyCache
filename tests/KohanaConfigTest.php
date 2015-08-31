<?php

namespace YPEarlyCache;

/**
 * 	@covers \YPEarlyCache\Config
 * 	@covers \YPEarlyCache\KohanaConfig
 */
class KohanaConfigTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var KohanaConfig
     */
    private $cfg1;

    /**
     * @var KohanaConfig
     */
    private $cfg2;

    public function setUp(){
        $this->cfg1 = new KohanaConfig(dirname(__FILE__) . '/data-example/kohana-config-1.php');
        $this->cfg2 = new KohanaConfig(dirname(__FILE__) . '/data-example/kohana-config-2.php');
    }

    /**
     * @cover KohanaConfig::__construct
     * @cover KohanaConfig::checkRequiredParams
     * @cover KohanaConfig::getConfigFileContent
     */
    public function testConstruct(){
        new KohanaConfig(dirname(__FILE__) . '/data-example/kohana-config-1.php');
    }

    public function testIsEnabled(){
        $this->assertTrue($this->cfg1->isEnabled());
        $this->assertFalse($this->cfg2->isEnabled());
    }

    public function testGetRules(){
        $this->assertTrue(is_array($this->cfg1->getRules()));
    }

    public function testGetCacheDir(){
        $this->assertTrue(is_string($this->cfg1->getCacheDir()));
    }

    public function testGetCookieNoCache() {
        $this->assertTrue(is_array($this->cfg1->getCookieNoCache()));
        $this->assertTrue(is_array($this->cfg2->getCookieNoCache()));
    }

    public function testNeedMinimizeHtml(){
        $this->assertTrue($this->cfg1->needMinimizeHtml());
        $this->assertFalse($this->cfg2->needMinimizeHtml());
    }

}