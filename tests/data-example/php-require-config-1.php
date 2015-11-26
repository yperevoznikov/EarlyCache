<?php

return array(
    'enabled' => true,
	'debug' => true,
    'cache_dir' => dirname(__FILE__) . '/cache-tmp',
    'cookie_no_cache' => 'authautologin',
    'minimize_html' => true,
    'secret_code' => '123',

    'rules' => array(
		array('exact' => 'page', 'cachetime' => 180),
        array('regexp' => '#^/$#', 'cachetime' => 60),
        array('startswith' => '/page', 'cachetime' => 0),
    ),

);