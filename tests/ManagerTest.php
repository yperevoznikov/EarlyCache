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
    private $cacheExamplesDir;

    private function delTree($dir) {
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    private function deleteFilesInTmpDir(){
        if (file_exists($this->tmpDir)) {
            $this->delTree($this->tmpDir);
        }
        mkdir($this->tmpDir);
    }

    public function setUp() {
        $this->tmpDir = dirname(__FILE__) . '/data-example/cache-tmp';
        $this->cacheExamplesDir = dirname(__FILE__) . '/data-example/cache-example-files';
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
        mkdir($this->tmpDir . DIRECTORY_SEPARATOR . 'tmp');
        file_put_contents($this->tmpDir . DIRECTORY_SEPARATOR . 'tmp/test2.txt', 'random content #2');

        // ensure that test files were really created: 2 created files + 2 misc ("./", "../") dirs
        $this->assertCount(4, $files = scandir($this->tmpDir));

        // create manager & remove all cache
        $env = new Environment(array(), array(), array());
        $mgr = new Manager($this->cfg1, $env);
        $mgr->deleteAllCache();

        // check that cache was deleted: remaining 2 virtual (".", "..") dirs
        $this->assertCount(2, $files = scandir($this->tmpDir));
    }

    /**
     * @dataProvider dataProviderTestSetCache
     */
    public function testSetCache($cfgName, $get, $server, $cookie, $expectedFilesCount) {

        $cfg = $this->{$cfgName};

        $env = new Environment($get, $server, $cookie);
        $mgr = new Manager($cfg, $env);
        $mgr->setCache('content', array('Content-Type' => 'text/html'), 200);

        // check that cache was created: cache files + 2 virtual (".", "..") files
        $this->assertCount($expectedFilesCount, $files = scandir($this->tmpDir));
    }

    public function dataProviderTestSetCache(){

        return array(
            array('cfg1', array(), array(), array(), 4),
            array('cfg1', array(), array('REQUEST_URI'=>'random/path/1'), array(), 2),
            array('cfg1', array('ec'=>'false'), array('REQUEST_URI'=>'/'), array(), 2),
            array('cfg1', array('early_cache'=>'false'), array('REQUEST_URI'=>'/'), array(), 2),
            array('cfg1', array('ec'=>'0'), array('REQUEST_URI'=>'/'), array(), 2),
            array('cfg1', array('early_cache'=>'0'), array('REQUEST_URI'=>'/'), array(), 2),
            array('cfg2', array(), array('REQUEST_URI'=>'/'), array(), 2),
        );
    }

    /**
     * @expectedException \YPEarlyCache\Exception\CacheDirectoryNotAvailableException
     */
    public function testSetCacheIfFilePermissionsDenied() {

        // delete targer folder - to it looks like permissions denied
        if (file_exists($this->tmpDir)) {
            $this->delTree($this->tmpDir);
        }

        $env = new Environment(array(), array(), array());
        $mgr = new Manager($this->cfg1, $env);
        $mgr->setCache('content', array('Content-Type' => 'text/html'), 200);
    }

    public function testFlushCacheIfAble()
    {
        $envMock = $this->getMockBuilder('YPEarlyCache\Environment')
            ->disableOriginalConstructor()
            ->getMock();
        $envMock->method('getUri')->willReturn("/");

        // pretend that we have cache
        copy(
            $this->cacheExamplesDir . '/6666cd76f96956469e7be39d750cc7d9',
            $this->tmpDir . '/6666cd76f96956469e7be39d750cc7d9'
        );
        copy(
            $this->cacheExamplesDir . '/6666cd76f96956469e7be39d750cc7d9.json',
            $this->tmpDir . '/6666cd76f96956469e7be39d750cc7d9.json'
        );
        $mgr = new Manager($this->cfg1, $envMock);

        // printOutput
        $envMock->method('printToOutput')->with($this->equalTo('content'));

        $mgr->flushCacheIfAble();
    }

    public function testFlushCacheIfAbleCacheDisabled()
    {
        $envMock = $this->getMockBuilder('YPEarlyCache\Environment')
            ->disableOriginalConstructor()
            ->getMock();
        $envMock->method('getUri')->willReturn("/");

        // if cache is disabled
        $mgr = new Manager($this->cfg2, $envMock);
        $result = $mgr->flushCacheIfAble();
        $this->assertFalse($result);
    }

    public function testFlushCacheIfAbleNoCacheFile()
    {
        $envMock = $this->getMockBuilder('YPEarlyCache\Environment')
            ->disableOriginalConstructor()
            ->getMock();
        $envMock->method('getUri')->willReturn("/");

        // if cache file was not found
        $mgr = new Manager($this->cfg1, $envMock);
        $result = $mgr->flushCacheIfAble();
        $this->assertFalse($result);
    }

    /**
     * @covers \YPEarlyCache\Manager::flushCacheIfAble
     */
    public function testFlushCacheIfAbleObsoleteCache()
    {
        $twoMonthInSeconds = 86400 * 60;

        $envMock = $this->getMockBuilder('YPEarlyCache\Environment')
            ->disableOriginalConstructor()
            ->getMock();

        $envMock->method('getUri')->willReturn("/");
        $envMock->expects($this->never())->method('printToOutput');
        $envMock->method('getTime')->willReturn(time() + $twoMonthInSeconds);

        copy(
            $this->cacheExamplesDir . '/6666cd76f96956469e7be39d750cc7d9',
            $this->tmpDir . '/6666cd76f96956469e7be39d750cc7d9'
        );
        copy(
            $this->cacheExamplesDir . '/6666cd76f96956469e7be39d750cc7d9.json',
            $this->tmpDir . '/6666cd76f96956469e7be39d750cc7d9.json'
        );

        $mgr = new Manager($this->cfg1, $envMock);

        $mgr->flushCacheIfAble();
    }

    /**
     * @dataProvider dataProviderTestFlushCacheIfAbleDisabledByUrl
     */
    public function testFlushCacheIfAbleDisabledByUrl($get, $server, $cookie) {

        $env = new Environment($get, $server, $cookie);

        // pretend that we have cache
        copy(
            $this->cacheExamplesDir . '/6666cd76f96956469e7be39d750cc7d9',
            $this->tmpDir . '/6666cd76f96956469e7be39d750cc7d9'
        );
        copy(
            $this->cacheExamplesDir . '/6666cd76f96956469e7be39d750cc7d9.json',
            $this->tmpDir . '/6666cd76f96956469e7be39d750cc7d9.json'
        );
        $mgr = new Manager($this->cfg1, $env);

        $result = $mgr->flushCacheIfAble();

        $this->assertFalse($result);
    }

    public function dataProviderTestFlushCacheIfAbleDisabledByUrl()
    {
        return array(
            array(array('ec'=>'false'), array('REQUEST_URI'=>'/'), array()),
            array(array('early_cache'=>'false'), array('REQUEST_URI'=>'/'), array()),
            array(array('ec'=>'0'), array('REQUEST_URI'=>'/'), array()),
            array(array('early_cache'=>'0'), array('REQUEST_URI'=>'/'), array()),
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

    /**
     * @expectedException \YPEarlyCache\Exception\WrongRuleException
     * @dataProvider dataProviderTestWrongRules
     */
    public function testWrongRules($cfg)
    {
        $envMock = $this->getMockBuilder('YPEarlyCache\Environment')
            ->disableOriginalConstructor()
            ->getMock();
        $envMock->method('getUri')->willReturn("/");
        $mgr = new Manager($cfg, $envMock);

        // pretend that we have cache
        copy(
            $this->cacheExamplesDir . '/6666cd76f96956469e7be39d750cc7d9',
            $this->tmpDir . '/6666cd76f96956469e7be39d750cc7d9'
        );
        copy(
            $this->cacheExamplesDir . '/6666cd76f96956469e7be39d750cc7d9.json',
            $this->tmpDir . '/6666cd76f96956469e7be39d750cc7d9.json'
        );

        // it won't be flushed
        $mgr->flushCacheIfAble();
    }

    public function dataProviderTestWrongRules()
    {
        return array(
            array(new PhpRequiredConfig(dirname(__FILE__) . '/data-example/php-require-config-wrong-2.php')),
            array(new PhpRequiredConfig(dirname(__FILE__) . '/data-example/php-require-config-wrong-3.php')),
            array(new PhpRequiredConfig(dirname(__FILE__) . '/data-example/php-require-config-wrong-4.php')),
        );
    }

}