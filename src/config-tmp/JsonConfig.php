<?php namespace YPEarlyCache\Config;

use YPEarlyCache\Contracts\IConfig;

class JsonConfig extends BaseConfig implements IConfig
{

	public function __construct($configPath)
	{
		$jsonStr = file_get_contents($configPath);

		$jsonObj = json_decode($jsonStr);

		//var_dump($jsonObj);
		//die;

		$this->enabled = 'true' == $jsonObj->enabled;
		$this->debug = 'true' == $jsonObj->enabled;
		$this->cacheDir = (string)$jsonObj->cacheDir;
		$this->minimizeHtml = 'true' == $jsonObj->minimizeHtml;
		$this->secretCode = (string)$jsonObj->secretCode;

		$this->cookieNoCache = array();
		foreach ($jsonObj->cookieNoCache as $cookieName)
		{
			$this->cookieNoCache[] = $cookieName;
		}

		$this->rules = array();
		foreach ($jsonObj->rules as $ruleMeta)
		{
			$this->rules[] = array(
				(string)$ruleMeta->rule => (string)$ruleMeta->value,
				"cachetime" => (int)$ruleMeta->cachetime,
			);
		}
	}

}