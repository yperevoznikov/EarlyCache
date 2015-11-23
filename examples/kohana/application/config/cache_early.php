<?php

const EARLY_CACHE_DAY = 86400;
const EARLY_CACHE_HOUR = 3600;
const EARLY_CACHE_MINUTE = 60;
const EARLY_CACHE_NONE = 0;

return array(
	'enabled' => true,
	'cache_dir' => dirname(__FILE__) . '/cache-tmp',
	'cookie_no_cache' => array('authautologin'),
	'minimize_html' => true,
	'secret_code' => 'secret-code', // has to be changed!

	'rules' => array(
		array('exact' => 'page', 'cachetime' => EARLY_CACHE_HOUR),
		array('startswith' => 'page', 'cachetime' => EARLY_CACHE_MINUTE),
		array('regexp' => '#^page#', 'cachetime' => EARLY_CACHE_NONE),
	),

);