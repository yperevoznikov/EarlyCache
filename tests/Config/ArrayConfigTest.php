<?php namespace YPEarlyCache\Config;

class ArrayConfigTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var ArrayConfig
	 */
	private $cfg1;

	/**
	 * @var ArrayConfig
	 */
	private $cfg2;

	/**
	 * @var ArrayConfig
	 */
	private $cfg3;

	public function setUp(){
		$arr1 = require(dirname(dirname(__FILE__)) . '/data-example/php-require-config-1.php');
		$this->cfg1 = new ArrayConfig($arr1);

		$arr2 = require(dirname(dirname(__FILE__)) . '/data-example/php-require-config-2.php');
		$this->cfg2 = new ArrayConfig($arr2);

		$arr3 = require(dirname(dirname(__FILE__)) . '/data-example/php-require-config-3.php');
		$this->cfg3 = new ArrayConfig($arr3);
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

	public function testIsDebug(){
		$this->assertTrue($this->cfg1->isDebug());
		$this->assertFalse($this->cfg2->isDebug());
		$this->assertFalse($this->cfg3->isDebug());
	}

}