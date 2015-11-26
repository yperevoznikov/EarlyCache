<?php namespace YPEarlyCache\Contracts;

interface IConfig
{

	/**
	 * @return boolean
	 */
	public function isEnabled();

	/**
	 * @return boolean
	 */
	public function isDebug();

	/**
	 * @return string
	 */
	public function getCacheDir();

	/**
	 * @return string
	 */
	public function getRules();

	/**
	 * @return string
	 */
	public function getSecretCode();

	/**
	 * @return array
	 */
    public function getCookieNoCache();

	/**
	 * @return bool
	 */
    public function needMinimizeHtml();

}