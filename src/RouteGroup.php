<?php
namespace IceAge;

class RouteGroup {
    protected $pattern;
    protected $routes = array();
    protected $middlewares = array();

    public function __construct($pattern = ''){
        $this->pattern = $pattern;
    }

    // shortcut for "GET" route
    public function get($pattern, $handler){
        return $this->route($pattern, $handler, 'GET');
    }
    
    // shortcut for "POST" route
    public function post($pattern, $handler){
        return $this->route($pattern, $handler, 'POST');
    }

    // shortcut for "PUT" route
    public function put($pattern, $handler){
        return $this->route($pattern, $handler, 'PUT');
    }

    // shortcut for "DELETE" route
    public function delete($pattern, $handler){
        return $this->route($pattern, $handler, 'DELETE');
    }

    // register a route
    public function route($pattern, $handler, $method = 'GET|POST'){
        $route = new Route($this->pattern.$pattern, $method);
        $this->routes[] = array('route' => $route, 'handler' => $handler);
        return $route;
    }

    public function match($path, $method){
        foreach($this->routes as $item){
            $route = $item['route'];
            $params = $route->match($path, $method);
            if($params){
                return array('route' => $route, 'route_params' => $params, 'handler' => $item['handler']); 
            }
        }

        return array();
    }

    public function middleware($handler, array $options = array()){
        $this->middlewares[] = array('handler' => $handler, 'options' => $options);
        return $this;
    }

    public function getMiddlewares(){
        return $this->middlewares;
    }
}