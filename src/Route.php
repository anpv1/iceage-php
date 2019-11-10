<?php
namespace IceAge;

class Route {
    protected $pattern = '/';
    protected $method = 'GET|POST';
    private $match_pattern = '';
    protected $middlewares = array();
    protected $param_pattern = '[\w\-\s\%]+';
    protected $matches = array();

    public function __construct($pattern = '/', $method = 'GET|POST'){
        $this->pattern = $pattern;
        $pattern = preg_replace('/(\:)([a-zA-Z_]{1}[a-zA-Z0-9_]*)(\|)([^\|]+)(\|)/', '(?P<${2}>${4})', $pattern);
        $this->match_pattern = '@^'.preg_replace('/(\:)([a-zA-Z_]{1}[a-zA-Z0-9_]*)/', '(?P<${2}>'.$this->param_pattern.')', $pattern).'/?$@';
        $this->method = $method;
    }

    public function match($path, $method){
        if( ! in_array( $method, explode('|', $this->method) ) ){
            return array();
        }

        preg_match_all('/(\:)([a-zA-Z_]{1}[a-zA-Z0-9_]*)/', $this->pattern, $names);        
        preg_match($this->match_pattern, $path, $matches);
        foreach($names[2] as $name){
            if(isset($matches[$name])){
                $matches[$name] = urldecode($matches[$name]);
            }
        }

        return $this->matches = $matches;
    }

    public function generate(array $params){
        $result = $this->processOptionalParameters($this->pattern, $params);
        foreach($params as $key => $value){
            $result = preg_replace('/:('.$key.')((\|)([^\|]+)(\|))?/', $value, $result);
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

    public function __get($name){
        return isset($this->matches[$name]) ? $this->matches[$name] : null;
    }

    protected function processOptionalParameters($pattern, $params){
        preg_match_all(
            '/\((\/)?\:([a-zA-Z_]{1}[a-zA-Z0-9_]*)((\|)([^\|]+)(\|))?([^(^:^)]+)?\)\?/', 
            $pattern, 
            $matches
        );
        if($matches && isset($matches[1]) && isset($matches[2][0])){
            $name = $matches[2][0];
            $replace = isset($params[$name]) ? $matches[1][0].$params[$name].$matches[7][0] : '';
            $pattern = str_replace($matches[0][0], $replace, $pattern);
            $pattern = $this->processOptionalParameters($pattern, $params);
        }
        return $pattern;
    }
}
