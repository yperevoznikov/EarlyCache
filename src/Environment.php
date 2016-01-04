<?php namespace YPEarlyCache;

/**
 * Class Environment
 * Implements access to environment
 *
 * @package YPEarlyCache
 */
class Environment
{
	private $currentResponseCode = 200;

	public function __construct(array $get, array $server, array $cookie)
	{
		$this->get = $get;
		$this->server = $server;
		$this->cookie = $cookie;
	}

	/**
	 * Sets HTTP header
	 * @param string $value
	 */
	public function setHeader($value)
	{
		header($value);
	}

	/**
	 * @param string $name
	 * @return string|null
	 */
	public function requestHeader($name)
	{
		$headers = getallheaders();
		if (isset($headers[$name])) {
			return $headers[$name];
		}
		return null;
	}

	/**
	 * Sets response code
	 *
	 * @param $code
	 * @param bool $forceUseHeader
	 * @return bool
	 */
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

	/**
	 * Flush content to standart output
	 * @param string $content
	 */
	public function printToOutput($content)
	{
		echo $content;
	}

	/**
	 * Stops executing code
	 */
	public function finishOutput()
	{
		// @codeCoverageIgnoreStart
		exit();
		// @codeCoverageIgnoreEnd
	}

	/**
	 * Get current timestamp
	 * @return int
	 */
	public function getTime()
	{
		// @codeCoverageIgnoreStart
		return time();
		// @codeCoverageIgnoreEnd
	}

	/**
	 * Check if cookie with name exists
	 * @param string $name
	 * @return bool
	 */
	public function hasCookieWithName($name)
	{
		return isset($this->cookie[$name]);
	}

	/**
	 * Gets current URI path
	 * @return string
	 */
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

	/**
	 * Returns $_GET params
	 * @param $name
	 * @param null|mixed $default
	 * @return null
	 */
	public function get($name, $default = null)
	{
		if (isset($this->get[$name])) {
			return $this->get[$name];
		} else {
			return $default;
		}
	}

}