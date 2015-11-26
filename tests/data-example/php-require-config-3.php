<?php

return array(
	'enabled' => true,
	'cache_dir' => dirname(__FILE__) . '/cache-tmp',
	'cookie_no_cache' => array('authautologin'),
	'minimize_html' => false,
	'secret_code' => '123',

	'rules' => array(
		array('startswith' => '', 'cachetime' => 60),
		array('regexp' => '#^page#', 'cachetime' => 0),
	),

);
