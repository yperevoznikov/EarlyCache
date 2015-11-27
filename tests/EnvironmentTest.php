<?php namespace YPEarlyCache;

/**
 * @covers \YPEarlyCache\Environment
 */
class EnvironmentTest extends \PHPUnit_Framework_TestCase
{

	/**
	 * @runInSeparateProcess
	 */
	public function testSetHeader()
	{
		$env = new Environment(array(), array(), array());

		$exampleHeader = 'Location : http://foo.com';
		$env->setHeader($exampleHeader);

		$this->assertContains($exampleHeader, xdebug_get_headers());
	}

	/**
	 * @runInSeparateProcess
	 * @dataProvider dataProviderTestSetResponseCode
	 */
	public function testSetResponseCode($forceUseHeader)
	{
		$env = new Environment(array(), array(), array());
		$result = $env->setResponseCode(404, $forceUseHeader);

		$this->assertTrue($result);

		if (function_exists('http_response_code')) {
			$this->assertEquals(404, http_response_code());
		} else {
			$this->assertContains('X-PHP-Response-Code: 404', xdebug_get_headers());
		}

		$result = $env->setResponseCode(200);
		$this->assertTrue($result);
		$result = $env->setResponseCode(200);
		$this->assertFalse($result);

		if (function_exists('http_response_code')) {
			$this->assertEquals(200, http_response_code());
		} else {
			$this->assertContains('X-PHP-Response-Code: 200', xdebug_get_headers());
		}
	}

	public function dataProviderTestSetResponseCode()
	{
		return array(array(true), array(false));
	}

	public function testPrintToOutput()
	{
		$env = new Environment(array(), array(), array());
		$expected = 'content';
		ob_start();
		$env->printToOutput($expected);
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals($expected, $actual);
	}

	public function testHasCookieWithName()
	{
		$env = new Environment(array(), array(), array('n1' => 'v1'));
		$this->assertTrue($env->hasCookieWithName('n1'));
		$this->assertFalse($env->hasCookieWithName('v1'));
	}

	public function testGetUri()
	{
		$server = array('REQUEST_URI' => '/page/test/');
		$env = new Environment(array(), $server, array());
		$this->assertEquals('/page/test', $env->getUri());

		$server = array('REQUEST_URI' => '/page/test');
		$env = new Environment(array(), $server, array());
		$this->assertEquals('/page/test', $env->getUri());

		$server = array('REQUEST_URI' => 'page/test');
		$env = new Environment(array(), $server, array());
		$this->assertEquals('/page/test', $env->getUri());

		$server = array('REQUEST_URI' => 'page/test/?n=v');
		$env = new Environment(array(), $server, array());
		$this->assertEquals('/page/test/?n=v', $env->getUri());

		$server = array();
		$env = new Environment(array(), $server, array());
		$this->assertEquals('/', $env->getUri());
	}

	public function testGet()
	{
		$get = array('n' => 'v');
		$env = new Environment($get, array(), array());
		$this->assertEquals('v', $env->get('n'));
		$this->assertNull($env->get('not-existed-param'));
	}

}