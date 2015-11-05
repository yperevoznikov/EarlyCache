<?php

namespace YPEarlyCache;

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

    private $cacheRule = null;

    public function __construct(Config $config, Environment $env){
        $this->config = $config;
        $this->env = $env;
    }

    public function deleteAllCache() {
        $files = scandir($this->config->getCacheDir());
        foreach ($files as $file) {
            if (in_array($file, array('.', '..'))) {
                continue;
            }
            unlink($this->config->getCacheDir() . DIRECTORY_SEPARATOR . $file);
        }
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

        if (false === $content || false === $meta) {
            return false;
        }

		$this->env->setHeader("Cache-Control: max-age=" . $this->getCacheTime());
		foreach ($meta->headers as $headerName => $headerValue) {
			$this->env->setHeader($headerName . ': ' . $headerValue);
		}

		if ($this->config->isDebug()) {
			$content = $this->addDebugInfo($content, $meta);
		}

        $this->env->printToOutput($content);
        $this->env->finishOutput();

        return true;
    }

    public function setCache($inContent, array $headers, $responseCode) {

        if (!$this->needSetCache()) {
            return;
        }

        $filepath = $this->getCacheFilepath();

        if ($this->config->needMinimizeHtml()) {
            $content = str_replace("\t", '', $inContent);
            $content = preg_replace("|\n *|", " ", $content);
            $content = preg_replace("| +|", ' ', $content);
        } else {
            $content = $inContent;
        }

        $meta = array(
			'time' => date('Y/m/d H:i:s'),
            'hash' => $this->getHashFromUrl(),
            'url' => $this->env->getUri(),
            'headers' => $headers,
            'code' => $responseCode,
            'rule' => $this->getCacheRule(),
        );

        // save content file and meta file
        if (
			false === file_put_contents($filepath, $content) ||
			false === file_put_contents($filepath . self::EXT_META, json_encode($meta))
		) {
			throw new \Exception('Could not write early cache to directory ' . $this->config->getCacheDir());
		}

    }

    private function getHashFromUrl() {
        return md5($this->env->getUri());
    }

    private function canGetCache() {

        $filepath = $this->getCacheFilepath();
        if (!file_exists($filepath) || !file_exists($filepath . self::EXT_META)) {
            return false;
        }

        if (0 == $this->getCacheTime()) {
            return false;
        }

        $modificationTimestamp = filemtime($filepath);
        if (time() - $modificationTimestamp > $this->getCacheTime()) {
            @unlink($filepath);
            @unlink($filepath . self::EXT_META);
            return false;
        }

        return true;
    }

    private function needSetCache() {

        if (false === $this->env->get('ec') || false === $this->env->get('early_cache')) {
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

        if (!isset($cacheRule['cachetime'])) {
            throw new \Exception('No `cachetime` defined for rule in EarlyCache rules: ' . print_r($cacheRule, true));
        }

        return $cacheRule['cachetime'];
    }

    private function getCacheRule() {
        if (!isset($this->cacheRule)) {
            foreach ($this->config->getRules() as $rule) {

				if (!is_array($rule)) {
					throw new \Exception('All rules have to be an array type');
				}

				if (!isset($rule['cachetime'])) {
					throw new \Exception('No `cachetime` in rule: ' . print_r($rule, true));
				}

				$availableMatches = array('exact', 'startswith', 'regexp',);
				if (isset($rule['exact'])) {
					$matchedRule = $rule['exact'] == $this->env->getUri();
				} elseif (isset($rule['startswith'])) {
                    $matchedRule = $rule['startswith'] == substr($this->env->getUri(), 0, strlen($rule['startswith']));
                } elseif (isset($rule['regexp'])) {
                    $matchedRule = (bool)preg_match($rule['regexp'], $this->env->getUri());
                } else {
					throw new \Exception('A rule does not have available match: ' . implode(', ', $availableMatches));
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