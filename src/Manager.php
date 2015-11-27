<?php namespace YPEarlyCache;

use YPEarlyCache\Contracts\IConfig;
use YPEarlyCache\Exception\CacheDirectoryNotAvailableException;
use YPEarlyCache\Exception\WrongRuleException;

class Manager {

	const EXT_META = '.json';

    /**
     * @var Config
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

    public function __construct(IConfig $config, Environment $env){
        $this->config = $config;
        $this->env = $env;
    }

    private function delTree($dir) {
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    public function deleteAllCache() {
        $this->delTree($this->config->getCacheDir());
        mkdir($this->config->getCacheDir());
    }

    public function flushCacheIfAble() {

        if (!$this->config->isEnabled()) {
            return false;
        }

        if (!$this->canGetCache()) {
            return false;
        }

        $content = file_get_contents($this->getCacheFilepath());
        $rawMeta = file_get_contents($this->getCacheFilepath() . self::EXT_META);
        $meta = json_decode($rawMeta);

		$this->env->setHeader("Cache-Control: max-age=" . $this->getCacheTime());
		foreach ($meta->headers as $header) {
			$this->env->setHeader($header->name . ': ' . $header->value);
		}

		if ($this->config->isDebug()) {
			$content = $this->addDebugInfo($content, $meta);
		}

        $this->env->setResponseCode($meta->code);

        $this->env->printToOutput($content);
        $this->env->finishOutput();

        return true;
    }

    public function setCache($inContent, array $headers, $responseCode) {

        if (!$this->needSetCache()) {
            return;
        }

        $filepath = $this->getCacheFilepath();

        $content = $inContent;
        if ($this->config->needMinimizeHtml()) {
            $content = str_replace("\t", '', $content);
            $content = preg_replace("|\n *|", " ", $content);
            $content = preg_replace("| +|", ' ', $content);
        }

        $hash = $this->getHashFromUrl();
        $meta = array(
            'hash' => $hash,
			'time' => date('Y/m/d H:i:s'),
            'url' => $this->env->getUri(),
            'headers' => $headers,
            'code' => $responseCode,
            'rule' => $this->getCacheRule(),
            'tags' => $this->getTags(),
        );

        if (count($this->getTags()) > 0) {
            foreach ($this->getTags() as $tag) {
                $this->addTagToIndex($tag, $hash);
            }
        }

        // save content file and meta file
        if (
			false === @file_put_contents($filepath, $content) ||
			false === @file_put_contents($filepath . self::EXT_META, json_encode($meta))
		) {
            $exceptionMes = 'Could not write early cache to directory ' . $this->config->getCacheDir();
            throw new CacheDirectoryNotAvailableException($exceptionMes);
		}
    }

    private function getHashFromUrl() {
        return md5($this->env->getUri());
    }

    private function canGetCache() {

        $allowedNegativeValue = array('0', 'false');
        if (in_array($this->env->get('ec'), $allowedNegativeValue) ||
            in_array($this->env->get('early_cache'), $allowedNegativeValue)
        ) {
            return false;
        }

        $filepath = $this->getCacheFilepath();
        if (!file_exists($filepath) || !file_exists($filepath . self::EXT_META)) {
            return false;
        }

        $modificationTimestamp = filemtime($filepath);
        if ($this->env->getTime() - $modificationTimestamp > $this->getCacheTime()) {
            @unlink($filepath);
            @unlink($filepath . self::EXT_META);
            return false;
        }

        return true;
    }

    private function needSetCache() {

        $allowedNegativeValue = array('0', 'false');
        if (in_array($this->env->get('ec'), $allowedNegativeValue) ||
            in_array($this->env->get('early_cache'), $allowedNegativeValue)
        ) {
            return false;
        }

        if (!$this->config->isEnabled()) {
            return false;
        }

        if ($this->getCacheTime() > 0) {
            return true;
        }

        return false;
    }

    private function getCacheTime() {
        if (false === $this->getCacheRule()) {
            return 0;
        }

        $cacheRule = $this->getCacheRule();

        return $cacheRule['cachetime'];
    }

    private function getCacheRule() {
        if (!isset($this->cacheRule)) {
            foreach ($this->config->getRules() as $rule) {

				if (!is_array($rule)) {
					throw new WrongRuleException('All rules have to be an array type');
				}

				if (!isset($rule['cachetime'])) {
					throw new WrongRuleException('No `cachetime` in rule: ' . print_r($rule, true));
				}

				$availableMatches = array('exact', 'startswith', 'regexp',);
				if (isset($rule['exact'])) {
					$matchedRule = $rule['exact'] == $this->env->getUri();
				} elseif (isset($rule['startswith'])) {
                    $matchedRule = $rule['startswith'] == substr($this->env->getUri(), 0, strlen($rule['startswith']));
                } elseif (isset($rule['regexp'])) {
                    $matchedRule = (bool)preg_match($rule['regexp'], $this->env->getUri());
                } else {
					throw new WrongRuleException('A rule does not have available match: ' . implode(', ', $availableMatches));
				}

                if ($matchedRule) {
                    $this->cacheRule = $rule;
                    return $this->cacheRule;
                }
            }
            $this->cacheRule = false;
        }
        return $this->cacheRule;
    }

    /**
     * @return string
     */
    private function getCacheFilepath()
    {
        $hash = $this->getHashFromUrl($this->env->getUri());
        $earlyCacheDir = $this->config->getCacheDir();
        $filepath = "{$earlyCacheDir}/{$hash}";
        return $filepath;
    }

    /**
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
     * @return \string[]
     */
    private function getTags()
    {
        return $this->tags;
    }

    /**
     * @param $tag
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

	private function addDebugInfo($inContent, $meta)
	{
		$content = $inContent;

		$debugContent  = "<pre>";
		$debugContent .= print_r($meta, true);
		$debugContent .= "</pre>";

		$content = str_replace('</body>', "$debugContent</body>", $content);

		return $content;
	}

}