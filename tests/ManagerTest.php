<?php

namespace YPEarlyCache;

/**
 * 	@covers \YPEarlyCache\Manager
 */
class ManagerTest extends \PHPUnit_Framework_TestCase {

    private $cfg1;
    private $cfg2;

    private $tmpDir;

    private function deleteFilesInTmpDir(){
        $files = scandir($this->tmpDir);
        foreach ($files as $file) {
            if (in_array($file, array('.', '..'))) {
                continue;
            }
            unlink($this->tmpDir . DIRECTORY_SEPARATOR . $file);
        }
    }
    
    public function setUp() {
        $this->tmpDir = dirname(__FILE__) . '/data-example/cache-tmp';
        $this->deleteFilesInTmpDir();
        $this->cfg1 = new KohanaConfig(dirname(__FILE__) . '/data-example/kohana-config-1.php');
        $this->cfg2 = new KohanaConfig(dirname(__FILE__) . '/data-example/kohana-config-2.php');
    }

    public function tearDown() {
        $this->deleteFilesInTmpDir();
    }

    public function testDeleteAllCache(){

        // emulate cache creation
        file_put_contents($this->tmpDir . DIRECTORY_SEPARATOR . 'test1.txt', 'random content #1');
        file_put_contents($this->tmpDir . DIRECTORY_SEPARATOR . 'test2.txt', 'random content #2');

        // ensure that test files were really created: 2 created files + 2 virtual (".", "..") files
        $this->assertCount(4, $files = scandir($this->tmpDir));

        // create manager & remove all cache
        $env = new Environment(array(), array(), array());
        $mgr = new Manager($this->cfg1, $env);
        $mgr->deleteAllCache();

        // check that cache was deleted: remaining 2 virtual (".", "..") files
        $this->assertCount(2, $files = scandir($this->tmpDir));

    }

	/**
	 * @dataProvider dataProviderTestSetCache
	 */
    public function testSetCache($get, $server, $cookie, $expectedFilesCount) {

		$env = new Environment($get, $server, $cookie);
		$mgr = new Manager($this->cfg1, $env);
		$mgr->setCache('content', 'text/html', 200);

		// check that cache was created: cache files + 2 virtual (".", "..") files
		$this->assertCount($expectedFilesCount, $files = scandir($this->tmpDir));

    }

	public function dataProviderTestSetCache(){
		return array(
			array(array(), array(), array(), 4),
			array(array(), array('REQUEST_URI'=>'rangom/path/1'), array(), 2),
		);
	}

}