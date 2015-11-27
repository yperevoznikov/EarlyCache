<?php

return array(
	'enabled' => true,
	'debug' => true,
	'cache_dir' => dirname(__FILE__) . '/cache-tmp',
	'cookie_no_cache' => 'authautologin',
	'minimize_html' => true,
	'secret_code' => '123',

	'rules' => array(
		'some-string', // Wrong: rule must be an array
	),

);