<?php

namespace YPEarlyCache;

/**
 * 	@covers \YPEarlyCache\Environment
 */
class EnvironmentTest extends \PHPUnit_Framework_TestCase {

    public function testHasCookieWithName() {
        $env = new Environment(array(), array(), array('n1' => 'v1'));
        $this->assertTrue($env->hasCookieWithName('n1'));
        $this->assertFalse($env->hasCookieWithName('v1'));
    }

    public function testGetUri() {
        $server = array('REQUEST_URI' => '/page/test/');
        $env = new Environment(array(), $server, array());
        $this->assertEquals('page/test', $env->getUri());

        $server = array('REQUEST_URI' => '/page/test');
        $env = new Environment(array(), $server, array());
        $this->assertEquals('page/test', $env->getUri());

        $server = array('REQUEST_URI' => 'page/test');
        $env = new Environment(array(), $server, array());
        $this->assertEquals('page/test', $env->getUri());

        $server = array('REQUEST_URI' => 'page/test/?n=v');
        $env = new Environment(array(), $server, array());
        $this->assertEquals('page/test/?n=v', $env->getUri());
    }

    public function testGet(){
        $get = array('n' => 'v');
        $env = new Environment($get, array(), array());
        $this->assertEquals('v', $env->get('n'));
        $this->assertNull($env->get('not-existed-param'));
    }

}