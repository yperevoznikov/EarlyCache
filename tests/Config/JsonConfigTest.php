<?php namespace YPEarlyCache\Config;

class JsonConfigTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var JsonConfig
	 */
	private $config1;

	/**
	 * @var JsonConfig
	 */
	private $config2;

	public function setUp()
	{
		$this->config1 = new JsonConfig(dirname(dirname(__FILE__)) . '/data-example/json-config-1.json');
		$this->config2 = new JsonConfig(dirname(dirname(__FILE__)) . '/data-example/json-config-2.json');
	}

	/**
	 * @covers YPEarlyCache\Config\XmlConfig::__construct
	 */
	public function testIsEnabled()
	{
		$this->assertTrue($this->config1->isEnabled());
		$this->assertFalse($this->config2->isEnabled());
	}

	public function testIsDebug()
	{
		$this->assertTrue($this->config1->isDebug());
		$this->assertFalse($this->config2->isDebug());
	}

	public function testGetCacheDir()
	{
		$this->assertEquals("data-example/cache-tmp", $this->config1->getCacheDir());
		$this->assertEquals("other-directory", $this->config2->getCacheDir());
	}

	public function testGetRules()
	{
		$rules1 = $this->config1->getRules();
		$this->assertCount(3, $rules1);
		$this->assertContains(array("exact"=>"page", "cachetime"=>180), $rules1);
		$this->assertContains(array("regexp"=>'#^/$#', "cachetime"=>60), $rules1);
		$this->assertContains(array("startswith"=>'/page', "cachetime"=>0), $rules1);

		$rules2 = $this->config2->getRules();
		$this->assertCount(6, $rules2);
	}

	public function testGetCookieNoCache()
	{
		$cookieNoCache1 = $this->config1->getCookieNoCache();
		$this->assertCount(1, $cookieNoCache1);
		$this->assertContains('authautologin-1', $cookieNoCache1);

		$cookieNoCache2 = $this->config2->getCookieNoCache();
		$this->assertCount(2, $cookieNoCache2);
		$this->assertContains('authautologin-2', $cookieNoCache2);
		$this->assertContains('authautologin-3', $cookieNoCache2);
	}

	public function testNeedMinimizeHtml()
	{
		$this->assertTrue($this->config1->needMinimizeHtml());
		$this->assertFalse($this->config2->needMinimizeHtml());
	}

	public function testGetSecretCode()
	{
		$this->assertEquals("secret-code-1", $this->config1->getSecretCode());
		$this->assertEquals("secret-code-2", $this->config2->getSecretCode());
	}

}