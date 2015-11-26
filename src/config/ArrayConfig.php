<?php namespace YPEarlyCache\Config;

use YPEarlyCache\Contracts\IConfig;
use YPEarlyCache\Exception\ConfigWrongException;

class ArrayConfig extends BaseConfig implements IConfig
{

	public function __construct($params){
		$this->checkRequiredParams($params);

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

		if (isset($params['debug'])) {
			$this->debug = $params['debug'];
		}

	}

	private function checkRequiredParams($params) {
		foreach ($this->requiredConfigParams as $requiredConfigParam) {
			if (!isset($params[$requiredConfigParam])) {
				$message = "Required config param in PHP Required Config missed: $requiredConfigParam\n";
				throw new ConfigWrongException($message);
			}
		}
	}

}