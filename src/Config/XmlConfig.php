<?php namespace YPEarlyCache\Config;

use YPEarlyCache\Contracts\IConfig;

class XmlConfig extends BaseConfig implements IConfig
{

	public function __construct($configPath)
	{
		$xml = simplexml_load_file($configPath);

		$this->enabled = 'true' == $xml->enabled;
		$this->debug = 'true' == $xml->enabled;
		$this->cacheDir = (string)$xml->cacheDir;
		$this->minimizeHtml = 'true' == $xml->minimizeHtml;
		$this->secretCode = (string)$xml->secretCode;

		$this->cookieNoCache = array();
		foreach ($xml->cookieNoCache->cookieName as $cookieName)
		{
			$this->cookieNoCache[] = $cookieName;
		}

		$this->rules = array();
		foreach ($xml->rules->children() as $key => $rule)
		{
			$this->rules[] = array(
				$key => (string)$rule,
				"cachetime" => (int)$rule['cachetime'],
			);
		}
	}
	
}