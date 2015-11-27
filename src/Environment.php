<?php namespace YPEarlyCache;
class Environment
{
	private $currentResponseCode = 200;

	public function __construct(array $get, array $server, array $cookie)
	{
		$this->get = $get;
		$this->server = $server;
		$this->cookie = $cookie;
	}

	public function setHeader($value)
	{
		header($value);
	}

	public function setResponseCode($code, $forceUseHeader = false)
	{
		if ($this->currentResponseCode == $code) {
			return false;
		}
		$this->currentResponseCode = $code;

		// For 4.3.0 <= PHP <= 5.4.0
		if (!function_exists('http_response_code') || $forceUseHeader) {
			header('X-PHP-Response-Code: ' . $code, true, $code);
		} else {
			http_response_code((int)$code);
		}

		return true;
	}

	public function printToOutput($content)
	{
		echo $content;
	}

	public function finishOutput()
	{
		// @codeCoverageIgnoreStart
		exit();
		// @codeCoverageIgnoreEnd
	}

	public function getTime()
	{
		// @codeCoverageIgnoreStart
		return time();
		// @codeCoverageIgnoreEnd
	}

	public function hasCookieWithName($name)
	{
		return isset($this->cookie[$name]);
	}

	public function getUri()
	{
		$uri = '/';
		if (isset($this->server['REQUEST_URI'])) {
			$uri = $this->server['REQUEST_URI'];
		}
		if (0 == substr_count($uri, '?')) {
			$uri = rtrim($uri, '/');
		}
		// Trim slash on left side and put one slash at the beginning
		$uri = '/' . ltrim($uri, '/');

		return $uri;
	}

	public function get($name, $default = null)
	{
		if (isset($this->get[$name])) {
			return $this->get[$name];
		} else {
			return $default;
		}
	}

}