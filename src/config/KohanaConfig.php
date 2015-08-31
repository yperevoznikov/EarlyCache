<?php

namespace YPEarlyCache;

class KohanaConfig extends Config {

    public function __construct($configPath){
        $params = $this->getConfigFileContent($configPath);
        $this->checkRequiredParams($params, $configPath);

        $this->enabled = $params['enabled'];
        $this->rules = $params['rules'];
        $this->cacheDir = $params['cache_dir'];

        if (isset($params['cookie_no_cache'])) {
            if (is_array($params['cookie_no_cache'])) {
                $this->cookieNoCache = $params['cookie_no_cache'];
            } else {
                $this->cookieNoCache = array($params['cookie_no_cache']);
            }
        }

        if (isset($params['minimize_html'])) {
            $this->minimizeHtml = $params['minimize_html'];
        }


    }

    private function checkRequiredParams($params, $configPath) {
        foreach ($this->requiredConfigParams as $requiredConfigParam) {
            if (!isset($params[$requiredConfigParam])) {
                $message = "Required config param in Kohana Config missed: $requiredConfigParam\n";
                $message .= "Config file: $configPath";
                throw new \Exception($message);
            }
        }
    }

    private function getConfigFileContent($configPath){
        if (!file_exists($configPath)) {
            throw new \Exception('Early cache Kohana config not found at path: ' . $configPath);
        }
        return require($configPath);
    }

}