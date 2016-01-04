<?php namespace YPEarlyCache;

use YPEarlyCache\Contracts\IConfig;
use YPEarlyCache\Exception\CacheDirectoryNotAvailableException;
use YPEarlyCache\Exception\WrongRuleException;

/**
 * Class Manager
 * Main class to manipulate with cache
 *
 * @package YPEarlyCache
 */
class Manager
{

	const EXT_META = '.json';

	/**
	 * @var IConfig
	 */
	private $config;

	/**
	 * @var Environment
	 */
	private $env;

	/**
	 * @var \string[]
	 */
	private $tags;

	/**
	 * @var array
	 */
	private $cacheRule = null;

	/**
	 * @var string|null
	 */
	private $etag = null;

	public function __construct(IConfig $config, Environment $env)
	{
		$this->config = $config;
		$this->env = $env;
	}

	private function delTree($dir)
	{
		$files = array_diff(scandir($dir), array('.', '..'));
		foreach ($files as $file) {
			(is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
		}

		return rmdir($dir);
	}

	public function deleteAllCache()
	{
		$this->delTree($this->config->getCacheDir());
		mkdir($this->config->getCacheDir());
	}

	/**
	 * Flush cached data to output if everything is OK
	 *
	 * @return bool
	 */
	public function flushCacheIfAble()
	{
		if (!$this->canGetCache()) {
			if ($this->isCacheOn()) {
				$this->etag = md5(uniqid());
			}
			return false;
		}

		$content = file_get_contents($this->getCacheFilepath());
		$rawMeta = file_get_contents($this->getCacheFilepath() . self::EXT_META);
		$meta = json_decode($rawMeta);

		if (isset($meta->etag)) {
			// Etag implementation
			$requestEtag = $this->env->requestHeader('ETag');
			if ($requestEtag == $meta->etag) {
				$this->env->setResponseCode(302);
				$this->env->finishOutput();
				return true;
			}
		}

		// Flush data to output
		$this->env->setHeader("Cache-Control: max-age=" . $this->getCacheTime());
		foreach ($meta->headers as $header) {
			$this->env->setHeader($header->name . ': ' . $header->value);
		}
		$this->env->setResponseCode($meta->code);
		$this->env->printToOutput($this->postprocessContent($content, $meta));
		$this->env->finishOutput();

		return true;
	}

	public function setCache($inContent, array $headers, $responseCode)
	{
		if (!$this->needSetCache()) {
			return;
		}

		$meta = array(
			'hash' => $this->getHashFromUrl(),
			'time' => date('Y/m/d H:i:s'),
			'url' => $this->env->getUri(),
			'headers' => $headers,
			'code' => $responseCode,
			'rule' => $this->getCacheRule(),
			'tags' => $this->getTags(),
			'etag' => $this->etag,
		);

		if (count($this->getTags()) > 0) {
			foreach ($this->getTags() as $tag) {
				$this->addTagToIndex($tag, $this->getHashFromUrl());
			}
		}

		// save content file and meta file
		if (
			false === @file_put_contents($this->getCacheFilepath() . self::EXT_META, json_encode($meta)) ||
			false === @file_put_contents($this->getCacheFilepath(), $this->preprocessContent($inContent))
		) {
			$exceptionMes = 'Could not write early cache to directory ' . $this->config->getCacheDir();
			throw new CacheDirectoryNotAvailableException($exceptionMes);
		}
	}

	private function getHashFromUrl()
	{
		return md5($this->env->getUri());
	}

	/**
	 * Checks that cache is available and valid
	 *
	 * @return bool
	 */
	private function canGetCache()
	{
		if (!$this->isCacheOn()) {
			return false;
		}

		$filepath = $this->getCacheFilepath();
		if (!file_exists($filepath) || !file_exists($filepath . self::EXT_META)) {
			return false;
		}

		if ($this->env->getTime() - filemtime($filepath) > $this->getCacheTime()) {
			@unlink($filepath);
			@unlink($filepath . self::EXT_META);

			return false;
		}

		return true;
	}

	private function needSetCache()
	{
		if (!$this->isCacheOn()) {
			return false;
		}

		if ($this->getCacheTime() > 0) {
			return true;
		}

		return false;
	}

	private function isCacheOn()
	{
		$allowedNegativeValue = array('0', 'false');
		if (in_array($this->env->get('ec'), $allowedNegativeValue) ||
			in_array($this->env->get('early_cache'), $allowedNegativeValue)
		) {
			return false;
		}

		if (!$this->config->isEnabled()) {
			return false;
		}

		return true;
	}

	private function getCacheTime()
	{
		if (false === ($cacheRule = $this->getCacheRule())) {
			return 0;
		}

		return $cacheRule['cachetime'];
	}

	private function getCacheRule()
	{
		if (!isset($this->cacheRule)) {
			foreach ($this->config->getRules() as $rule) {

				if (!is_array($rule)) {
					throw new WrongRuleException('All rules must be arrays');
				}

				if (!isset($rule['cachetime'])) {
					throw new WrongRuleException('No `cachetime` in rule: ' . print_r($rule, true));
				}

				if ($this->isRuleMatchedWithUrl($rule)) {
					return $this->cacheRule = $rule;
				}
			}
			$this->cacheRule = false;
		}

		return $this->cacheRule;
	}

	/**
	 * Get filepath of file based on current URL
	 *
	 * @return string
	 */
	private function getCacheFilepath()
	{
		return $this->config->getCacheDir() . '/' . $this->getHashFromUrl();
	}

	/**
	 * Adds tag to current session to be added to session of current response
	 *
	 * @param string|array $tagName
	 */
	public function addTag($tagName)
	{
		if (is_array($tagName)) {
			foreach ($tagName as $tagNameItem) {
				if (is_string($tagNameItem)) {
					$this->tags[] = $tagNameItem;
				}
			}
		} elseif (is_string($tagName)) {
			$this->tags[] = $tagName;
		}
		$this->tags = array_values(array_unique($this->tags));
	}

	/**
	 * Get tags that were added through the session so far
	 *
	 * @return \string[]
	 */
	private function getTags()
	{
		return $this->tags;
	}

	/**
	 * Deletes cache from storage by tag and returns caches that been removed
	 *
	 * @param string $tag
	 * @return int
	 */
	public function deleteByTag($tag)
	{
		$deletedCount = 0;

		$tagsIndexFilepath = $this->config->getCacheDir() . "/tagsIndex/" . $tag . '.json';
		if (file_exists($tagsIndexFilepath)) {
			$tagsIndexContent = file_get_contents($tagsIndexFilepath);
			$tagsIndexArr = json_decode($tagsIndexContent);

			if (false !== $tagsIndexArr) {
				foreach ($tagsIndexArr as $tagsIndexHash) {
					unlink($this->config->getCacheDir() . '/' . $tagsIndexHash);
					unlink($this->config->getCacheDir() . '/' . $tagsIndexHash . '.json');
					$deletedCount++;
				}
			}
			unlink($tagsIndexFilepath);
		}

		return $deletedCount;
	}

	/**
	 * @param string $tag
	 * @param string $hash
	 */
	private function addTagToIndex($tag, $hash)
	{
		$tagsIndexDir = $this->config->getCacheDir() . '/tagsIndex';
		if (!file_exists($tagsIndexDir)) {
			mkdir($tagsIndexDir);
		}

		$tagsIndexFilepath = $tagsIndexDir . '/' . $tag . '.json';
		$jsonContent = file_exists($tagsIndexFilepath) ? file_get_contents($tagsIndexFilepath) : '[]';
		$jsonArr = json_decode($jsonContent);
		$jsonArr = is_array($jsonArr) ? $jsonArr : array();

		$jsonArr[] = $hash;
		$jsonArr = array_unique($jsonArr);

		$jsonContent = json_encode($jsonArr);
		file_put_contents($tagsIndexFilepath, $jsonContent);
	}

	/**
	 * @param string $content
	 * @param array $meta
	 * @return string
	 */
	private function addDebugInfo($content, $meta)
	{
		$debugContent = "<pre>";
		$debugContent .= print_r($meta, true);
		$debugContent .= "</pre>";

		return str_replace('</body>', "$debugContent</body>", $content);
	}

	/**
	 * Modify content BEFORE put to cache storage
	 *
	 * @param string $content
	 * @return string
	 */
	private function preprocessContent($content)
	{
		if ($this->config->needMinimizeHtml()) {
			$content = str_replace("\t", '', $content);
			$content = preg_replace("|\n *|", " ", $content);
			$content = preg_replace("| +|", ' ', $content);
		}

		return $content;
	}

	/**
	 * Modify content AFTER get from storage and BEFORE flush to output
	 *
	 * @param string $content
	 * @param array $meta
	 * @return string
	 */
	private function postprocessContent($content, $meta)
	{
		if ($this->config->isDebug()) {
			$content = $this->addDebugInfo($content, $meta);
		}

		return $content;
	}

	/**
	 * @throws WrongRuleException
	 * @param array $rule
	 * @return bool
	 */
	private function isRuleMatchedWithUrl($rule)
	{
		$availableMatches = array('exact', 'startswith', 'regexp',);
		if (isset($rule['exact'])) {
			return $rule['exact'] == $this->env->getUri();
		} elseif (isset($rule['startswith'])) {
			return $rule['startswith'] == substr($this->env->getUri(), 0, strlen($rule['startswith']));
		} elseif (isset($rule['regexp'])) {
			return (bool)preg_match($rule['regexp'], $this->env->getUri());
		} else {
			throw new WrongRuleException('A rule does not have available match: ' . implode(', ', $availableMatches));
		}
	}

}