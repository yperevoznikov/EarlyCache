<?php namespace YPEarlyCache\Config;

abstract class BaseConfig {

	/**
	 * @var bool
	 */
	protected $enabled = false;

	/**
	 * @var bool
	 */
	protected $debug = false;

    /**
     * @var string
     */
    protected $cacheDir;

    /**
     * @var string
     */
    protected $secretCode;

    /**
     * @var array
     */
    protected $rules = array();

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
	 * @return boolean
	 */
	public function isDebug()
	{
		return $this->debug;
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
    public function getSecretCode()
    {
        return $this->secretCode;
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
     * @return bool
     */
    public function needMinimizeHtml()
    {
        return $this->minimizeHtml;
    }

}