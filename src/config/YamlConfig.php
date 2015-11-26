<?php namespace YPEarlyCache\Config;

use Symfony\Component\Yaml\Yaml;
use YPEarlyCache\Contracts\IConfig;

class YamlConfig extends BaseConfig implements IConfig
{

	public function __construct($configPath)
	{
		$yaml = Yaml::parse(file_get_contents($configPath));

		$this->enabled = $yaml['earlyCache']['enabled'];
		$this->debug = $yaml['earlyCache']['debug'];
		$this->cacheDir = $yaml['earlyCache']['cacheDir'];
		$this->minimizeHtml = $yaml['earlyCache']['minimizeHtml'];
		$this->secretCode = $yaml['earlyCache']['secretCode'];

		$this->cookieNoCache = array();
		if (is_string($yaml['earlyCache']['cookieNoCache'])) {
			$this->cookieNoCache = array($yaml['earlyCache']['cookieNoCache']);
		} else {
			foreach ($yaml['earlyCache']['cookieNoCache'] as $cookieName)
			{
				$this->cookieNoCache[] = $cookieName;
			}
		}

		$this->rules = array();
		foreach ($yaml['earlyCache']['rules'] as $rule)
		{
			$this->rules[] = $rule;
		}
	}
}