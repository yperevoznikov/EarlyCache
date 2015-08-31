<?php

namespace YPEarlyCache;

class Environment
{

    public function __construct(array $get, array $server, array $cookie){
        $this->get = $get;
        $this->server = $server;
        $this->cookie = $cookie;
    }

    public function setHeader($value){
        header($value);
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
        $uri = '';
        if (isset($this->server['REQUEST_URI'])) {
            $uri = $this->server['REQUEST_URI'];
        }

        $uri = ltrim($uri, '/');

        if (0 == substr_count($uri, '?')) {
            $uri = rtrim($uri, '/');
        }

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