<?php

namespace YPEarlyCache;

class Manager {

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
        if (!$this->canGetCache()) {
            return false;
        }

        $content = file_get_contents($this->getCacheFilepath());
        $rawMeta = file_get_contents($this->getCacheFilepath() . '.meta');
        $meta = json_decode($rawMeta);

        if (false === $content || false === $meta) {
            return false;
        }

        $this->env->setHeader("Cache-Control: max-age=" . $this->getCacheTime());
        $this->env->setHeader("Content-Type: " . $meta->memo);
        $this->env->printToOutput($content);
        $this->env->finishOutput();

        return true;
    }

    public function setCache($inContent, $memoType, $responseCode) {

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
            'hash' => $this->getHashFromUrl(),
            'url' => $this->env->getUri(),
            'memo' => $memoType,
            'code' => $responseCode,
            'rule' => $this->getCacheRule(),
            'tags' => $this->getTags(),
        );

        // save content file
        file_put_contents($filepath, $content);

        // save meta file
        file_put_contents($filepath . '.json', json_encode($meta));
    }

    private function getHashFromUrl() {
        return md5($this->env->getUri());
    }

    private function canGetCache() {

        $filepath = $this->getCacheFilepath();
        if (!file_exists($filepath) || !file_exists($filepath . '.json')) {
            return false;
        }

        if (0 == $this->getCacheTime()) {
            return false;
        }

        $modificationTimestamp = filemtime($filepath);
        if (time() - $modificationTimestamp > $this->getCacheTime()) {
            @unlink($filepath);
            @unlink($filepath . '.json');
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
        if (null === $this->getCacheRule()) {
            return 0;
        }

        $cacheRule = $this->getCacheRule();

        if (!isset($cacheRule['cachetime'])) {
            throw new \Exception('No `cachetime` defined for rule in EarlyCache rules');
        }

        return $cacheRule['cachetime'];
    }

    private function getCacheRule() {
        if (!isset($this->cacheRule)) {
            foreach ($this->config->getRules() as $rule) {

                $matchedRule = false;
                if (isset($rule['startswith'])) {
                    $matchedRule = $rule['startswith'] == substr($this->env->getUri(), 0, strlen($rule['startswith']));
                } elseif (isset($rule['regexp'])) {
                    $matchedRule = (bool)preg_match($rule['regexp'], $this->env->getUri());
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
        $files = scandir($this->config->getCacheDir());
        foreach ($files as $file) {
            if (($temp = strlen($file) - strlen('.json')) >= 0 && strpos($file, '.json', $temp) !== FALSE) {
                $fileJsonFullpath = $this->config->getCacheDir() . '/' . $file;
                $jsonContent = file_get_contents($fileJsonFullpath);
                $jsonObj = json_decode($jsonContent);
                if (is_object($jsonObj) && is_array($jsonObj->tags) && in_array($tag, $jsonObj->tags)) {
                    $contentFilename = substr($fileJsonFullpath, 0, strlen($fileJsonFullpath) - 5);
                    unlink($contentFilename);
                    unlink($fileJsonFullpath);
                    $deletedCount++;
                }
            }
        }
        return $deletedCount;
    }

}