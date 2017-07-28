<?php
namespace IceAge;

class Route {
    protected $pattern = '/';
    protected $method = 'GET|POST';
    private $match_pattern = '';
    protected $middlewares = array();

    public function __construct($pattern = '/', $method = 'GET|POST', $param_pattern = '[\w\-\s\%]+'){
        $this->pattern = $pattern;
        $this->match_pattern = '@^'.preg_replace('/(\:)([a-zA-Z_]+\w+)/', '(?P<${2}>'.$param_pattern.')', $pattern).'/?$@';
        $this->method = $method;
    }

    public function match($path, $method){
        if( ! in_array( $method, explode('|', $this->method) ) ){
            return false;
        }

        preg_match_all('/(\:)([a-zA-Z_]+\w+)/', $this->pattern, $names);        
        preg_match($this->match_pattern, $path, $matches);
        foreach($names[2] as $name){
            if(isset($matches[$name])){
                $matches[$name] = urldecode($matches[$name]);
            }
        }

        return $matches;
    }

    public function generate(array $params){
        $result = $this->pattern;
        foreach($params as $key => $value){
            $result = str_replace(':'.$key, $value, $result);
        }
        return $result;
    }

    public function middleware($handler, array $options = array()){
        $this->middlewares[] = array('handler' => $handler, 'options' => $options);
        return $this;
    }

    public function getMiddlewares(){
        return $this->middlewares;
    }
}
