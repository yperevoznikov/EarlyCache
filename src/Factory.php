<?php namespace YPEarlyCache;

use YPEarlyCache\Config\JsonConfig;
use YPEarlyCache\Config\PhpRequiredConfig;
use YPEarlyCache\Config\XmlConfig;
use YPEarlyCache\Config\YamlConfig;
use YPEarlyCache\Contracts\IConfig;

class Factory
{

    const CONFIG_PHP_REQUIRED = "php_required";
    const CONFIG_JSON = "json";
    const CONFIG_XML = "xml";
    const CONFIG_YAML = "yaml";
    const CONFIG_ARRAY = "array";
    const CONFIG_CONFIG_OBJECT = "config_object";

	/**
     * @param string $source
     * @param string|null $configType
     * @param array|null $get
     * @param array|null $server
     * @param array|null $cookie
     * @return null|Manager
     */
    public static function getManager(
        $source,
        $configType=null,
        array $get=null,
        array $server=null,
        array $cookie=null
    ){

		$get = isset($get) ? $get : $_GET;
		$server = isset($server) ? $server : $_SERVER;
		$cookie = isset($cookie) ? $cookie : $_COOKIE;

        $env = new Environment($get, $server, $cookie);

        $configType = isset($configType) ? $configType : self::getConfigTypeByFilename($source);

        $manager = null;
        switch ($configType) {
            case self::CONFIG_JSON:
                $manager = new Manager(new JsonConfig($source), $env);
                break;
            case self::CONFIG_PHP_REQUIRED:
                $manager = new Manager(new PhpRequiredConfig($source), $env);
                break;
            case self::CONFIG_XML:
                $manager = new Manager(new XmlConfig($source), $env);
                break;
            case self::CONFIG_YAML:
                $manager = new Manager(new YamlConfig($source), $env);
                break;
            case self::CONFIG_ARRAY:
                $manager = new Manager(new YamlConfig($source), $env);
                break;
            case self::CONFIG_CONFIG_OBJECT:
                $manager = new Manager(new $source, $env);
                break;
        }

        return $manager;
    }

    /**
     * @param string|array $source
     * @return string
     */
    private function getConfigTypeByFilename($source)
    {
        $configType = "";

        $fileExt = pathinfo($source, PATHINFO_EXTENSION);
        if ($source instanceof IConfig) {
            $configType = self::CONFIG_CONFIG_OBJECT;
        } elseif (is_array($source)) {
            $configType = self::CONFIG_ARRAY;
        } elseif ("php" == $fileExt) {
            $configType = self::CONFIG_PHP_REQUIRED;
        } elseif ("json" == $fileExt) {
            $configType = self::CONFIG_JSON;
        } elseif ("xml" == $fileExt) {
            $configType = self::CONFIG_XML;
        } elseif ("jaml" == $fileExt) {
            $configType = self::CONFIG_YAML;
        }

        return $configType;
    }

}