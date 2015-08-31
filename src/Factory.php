<?php

namespace YPEarlyCache;

class Factory
{

    const KOHANA = 'kohana';

    public static function getManager($platform, $configPath, array $get, array $server, array $cookie){

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