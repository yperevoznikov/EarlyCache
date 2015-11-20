<?php

namespace YPEarlyCache;

/**
 * 	@covers \YPEarlyCache\Manager
 */
class ManagerTest extends \PHPUnit_Framework_TestCase {

    private $cfg1;
    private $cfg2;
    private $cfg3;

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
        $this->cfg3 = new KohanaConfig(dirname(__FILE__) . '/data-example/kohana-config-3.php');
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

    public function testSetCache() {
        $env = new Environment(array(), array(), array());
        $mgr = new Manager($this->cfg3, $env);
        $mgr->setCache('content', 'text/html', 200);

        // check that cache was created: content file + meta file + 2 virtual (".", "..") files
        $this->assertCount(4, $files = scandir($this->tmpDir));
    }

    public function testTags() {
        $env = new Environment(array(), array(), array());
        $mgr = new Manager($this->cfg1, $env);

        $mgr->addTag('tag1');
        $mgr->addTag(array('tag1', 'tag2'));
        $mgr->addTag(array('tag2', array('tag3')));

        $mgr->setCache('content', 'text/html', 200);

        // created filepath
        $jsonFilepath = $this->tmpDir . '/d41d8cd98f00b204e9800998ecf8427e.json';

        $this->assertFileExists($jsonFilepath);

        // get meta file
        $jsonFileContent = file_get_contents($jsonFilepath);
        $jsonObj = json_decode($jsonFileContent);

        $this->assertContains('tag1', $jsonObj->tags);
        $this->assertContains('tag2', $jsonObj->tags);
        $this->assertcount(2, $jsonObj->tags);

        $deletedCount = $mgr->deleteByTag('not-existed-tag');
        $this->assertEquals(0, $deletedCount);
        $this->assertFileExists($jsonFilepath);

        $deletedCount = $mgr->deleteByTag('tag1');
        $this->assertEquals(1, $deletedCount);
        $this->assertFileNotExists($jsonFilepath);

    }

}