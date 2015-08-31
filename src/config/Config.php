<?php

namespace YPEarlyCache;

abstract class Config {

    /**
     * @var bool
     */
    protected $enabled = false;

    /**
     * @var string
     */
    protected $cacheDir;

    /**
     * @var array
     */
    protected $rules = '';

    /**
     * @var array
     */
    protected $cookieNoCache = array();

    protected $requiredConfigParams = array('enabled', 'rules', 'cache_dir');

    protected $minimizeHtml = false;

    /**
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @return string
     */
    public function getCacheDir()
    {
        return $this->cacheDir;
    }

    /**
     * @return string
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * @return array
     */
    public function getCookieNoCache()
    {
        return $this->cookieNoCache;
    }

    /**
     * @var bool
     */
    public function needMinimizeHtml(){
        return $this->minimizeHtml;
    }

}