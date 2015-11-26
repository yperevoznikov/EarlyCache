<?php namespace YPEarlyCache;

use PHPUnit_Framework_TestCase;
use YPEarlyCache\Config\PhpRequiredConfig;

/**
 * 	@covers \YPEarlyCache\Manager
 */
class ManagerTest extends PHPUnit_Framework_TestCase {

    private $cfg1;
    private $cfg2;
    private $cfg3;

    private $tmpDir;

    private function delTree($dir) {
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    private function deleteFilesInTmpDir(){
        $this->delTree($this->tmpDir);
        mkdir($this->tmpDir);
    }

    public function setUp() {
        $this->tmpDir = dirname(__FILE__) . '/data-example/cache-tmp';
        $this->deleteFilesInTmpDir();
        $this->cfg1 = new PhpRequiredConfig(dirname(__FILE__) . '/data-example/php-require-config-1.php');
        $this->cfg2 = new PhpRequiredConfig(dirname(__FILE__) . '/data-example/php-require-config-2.php');
        $this->cfg3 = new PhpRequiredConfig(dirname(__FILE__) . '/data-example/php-require-config-3.php');
    }

    public function tearDown() {
        $this->deleteFilesInTmpDir();
    }

    public function testDeleteAllCache(){

        // emulate cache creation
        file_put_contents($this->tmpDir . DIRECTORY_SEPARATOR . 'test1.txt', 'random content #1');
        file_put_contents($this->tmpDir . DIRECTORY_SEPARATOR . 'test2.txt', 'random content #2');

        // ensure that test files were really created: 2 created files + 2 misc ("./", "../") dirs
        $this->assertCount(4, $files = scandir($this->tmpDir));

        // create manager & remove all cache
        $env = new Environment(array(), array(), array());
        $mgr = new Manager($this->cfg1, $env);
        $mgr->deleteAllCache();

        // check that cache was deleted: remaining 2 virtual (".", "..") dirs
        $this->assertCount(2, $files = scandir($this->tmpDir));
    }

    // public function testSetCache() {
    //     $env = new Environment(array(), array(), array());
    //     $mgr = new Manager($this->cfg3, $env);
    //     $mgr->setCache('content', 'text/html', 200);

    //     // check that cache was created: content file + meta file + 2 misc ("./", "../") dirs
    //     $this->assertCount(4, $files = scandir($this->tmpDir));
    // }

    /**
     * @dataProvider dataProviderTestSetCache
     */
    public function testSetCache($get, $server, $cookie, $expectedFilesCount) {

        $env = new Environment($get, $server, $cookie);
        $mgr = new Manager($this->cfg1, $env);
        $mgr->setCache('content', array('Content-Type' => 'text/html'), 200);

        // check that cache was created: cache files + 2 virtual (".", "..") files
        $this->assertCount($expectedFilesCount, $files = scandir($this->tmpDir));

    }

    public function testFlushCacheIfAble()
    {
        //$envMock = $this->getMockBuilder('YPEarlyCache\Environment')
        //    ->disableOriginalConstructor()
        //    ->getMock();
		//
        //$mgr = new Manager($this->cfg1, $envMock);
        //$mgr->flushCacheIfAble();
    }

    public function dataProviderTestSetCache(){
        return array(
            array(array(), array(), array(), 4),
            array(array(), array('REQUEST_URI'=>'rangom/path/1'), array(), 2),
        );
    }

    public function testTags() {
        $env = new Environment(array(), array(), array());
        $mgr = new Manager($this->cfg1, $env);

        $mgr->addTag('tag1');
        $mgr->addTag(array('tag1', 'tag2'));
        $mgr->addTag(array('tag2', array('tag3')));

        $mgr->setCache('content', array('Content-Type' => 'text/html'), 200);

        // created filepath
        $jsonFilepath = $this->tmpDir . '/6666cd76f96956469e7be39d750cc7d9.json';
        $this->assertFileExists($jsonFilepath);

        // get meta file
        $jsonFileContent = file_get_contents($jsonFilepath);
        $jsonObj = json_decode($jsonFileContent);

        $this->assertContains('tag1', $jsonObj->tags);
        $this->assertContains('tag2', $jsonObj->tags);
        $this->assertcount(2, $jsonObj->tags);

        $this->assertFileExists($this->tmpDir . '/tagsIndex/tag1.json');
        $this->assertFileExists($this->tmpDir . '/tagsIndex/tag2.json');

        $deletedCount = $mgr->deleteByTag('not-existed-tag');
        $this->assertEquals(0, $deletedCount);
        $this->assertFileExists($jsonFilepath);

        $deletedCount = $mgr->deleteByTag('tag1');
        $this->assertEquals(1, $deletedCount);
        $this->assertFileNotExists($jsonFilepath);

    }

}