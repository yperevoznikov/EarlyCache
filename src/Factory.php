<?php

namespace YPEarlyCache;

class Factory
{

    const KOHANA = 'kohana';

    public static function getManager($platform, $configPath, array $get=null, array $server=null, array $cookie=null){

		$get = isset($get) ? $get : $_GET;
		$server = isset($server) ? $server : $_SERVER;
		$cookie = isset($cookie) ? $cookie : $_COOKIE;

        $env = new Environment($get, $server, $cookie);

        $manager = null;
        switch ($platform) {
            case self::KOHANA:
                $manager = new Manager(new KohanaConfig($configPath), $env);
                break;
        }

        return $manager;
    }

}