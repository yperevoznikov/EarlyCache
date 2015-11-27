<?php namespace YPEarlyCache;

class Environment
{
    const DEFAULT_RESPONSE_CODE = 200;

    public function __construct(array $get, array $server, array $cookie){
        $this->get = $get;
        $this->server = $server;
        $this->cookie = $cookie;
    }

    public function setHeader($value){
        header($value);
    }

    public function setResponseCode($code)
    {
        if (self::DEFAULT_RESPONSE_CODE == $code) {
            return;
        }

        // For 4.3.0 <= PHP <= 5.4.0
        if (function_exists('http_response_code')) {
            http_response_code((int)$code);
        } else {
            header('X-PHP-Response-Code: ' . $code, true, $code);
        }
    }

    public function printToOutput($content){
        echo $content;
    }

    public function finishOutput(){
        exit();
    }

    public function hasCookieWithName($name){
        return isset($this->cookie[$name]);
    }

    public function getUri() {
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

    public function get($name, $default=null){
        if (isset($this->get[$name])) {
            return $this->get[$name];
        } else {
            return $default;
        }
    }
    
}