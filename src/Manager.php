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

//    private function earlyCacheGetType($path) {
//
//        $pathParts = explode("?", $path);
//        if (0 === count($pathParts)) {
//            return 'home';
//        }
//        $pathDir = trim(array_shift($pathParts), '/\\');
//        if ('' === $pathDir) {
//            return 'home';
//        }
//        if ('admin' === substr($pathDir, 0, 5)) {
//            return 'admin';
//        }
//        if ('auth' === substr($pathDir, 0, 4)) {
//            return 'auth';
//        }
//        if ('game' === substr($pathDir, 0, 4)) {
//            return 'game';
//        }
//        if ('category' === substr($pathDir, 0, 8)) {
//            return 'category';
//        }
//        if ('tag' === substr($pathDir, 0, 3)) {
//            return 'tag';
//        }
//        if ('selection' === substr($pathDir, 0, 3)) {
//            return 'selection';
//        }
//        if ('search' === substr($pathDir, 0, 6)) {
//            return 'search';
//        }
//        if ('video' === substr($pathDir, 0, 5)) {
//            return 'video';
//        }
//        if ('actors' === substr($pathDir, 0, 6)) {
//            return 'actor';
//        }
//        if ('actor' === substr($pathDir, 0, 6)) {
//            return 'actor';
//        }
//        if ('actress' === substr($pathDir, 0, 7)) {
//            return 'actor';
//        }
//        if ('page' === substr($pathDir, 0, 4)) {
//            return 'page';
//        }
//
//        return 'video_page';
//    }
//
//    private function earlyCacheGetCacheSeconds($type) {
//
//        if (isset($_GET["early_cache_type"]) && "true" == $_GET["early_cache_type"]) {
//            echo $type;
//        }
//
//        switch ($type) {
//            case 'video_page':
//            case 'video':
//    //            return 86400;
//    //            return 14400;
//                return 7200;
//                break;
//            case 'tag':
//            case 'selection':
//            case 'category':
//            case 'actor':
//                return 3600;
//    //				return 1800;
//                break;
//            case 'home':
//                return 300;
//                break;
//            case 'game':
//            case 'admin':
//            case 'search':
//            case 'page':
//            default:
//                return 0;
//        }
//    }
//
//    private function earlyCacheNeedCache($uri) {
//        $type = $this->earlyCacheGetType($uri);
//        if ($this->earlyCacheGetCacheSeconds($type) > 0) {
//            return true;
//        } else {
//            return false;
//        }
//    }
//
//    private function earlyCacheGetHtml($uri) {
//
//        global $early_cache_dir;
//
//        $type = $this->earlyCacheGetType($uri);
//        $secondsToCache = $this->earlyCacheGetCacheSeconds($type);
//        $hash = md5($uri);
//        $filepath = "{$early_cache_dir}/{$hash}";
//        if (!file_exists($filepath)) {
//            return false;
//        }
//        $modificationTimestamp = filemtime($filepath);
//        if (time() - $modificationTimestamp > $secondsToCache) {
//            unlink($filepath);
//            return false;
//        }
//        return file_get_contents($filepath);
//    }
//

}