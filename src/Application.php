<?php
namespace IceAge;

class Application
{
    public static $instance = null;

    protected $routes = array();
    protected $groups = array();
    protected $services = array();
    protected $active_route = null;
    protected $server;
    protected $get;
    protected $post;

    public static function getOrCreate(array $server = array(), array $get = array(), array $post = array()){
        if(!self::$instance){
            self::$instance = new Application($server, $get, $post);
        }

        return self::$instance;
    }

    public function __construct(array $server = array(), array $get = array(), array $post = array()){
        $this->server = $server ? $server : $_SERVER;
        $this->get = $get ? $get : $_GET;
        $this->post = $post ? $post : $_POST;
    }

    public function get_active_route(){
        return $this->active_route;
    }


    public function bootstrap(array $services){
        foreach ($services as $handler) {
            if(is_callable($handler)){
                call_user_func_array($handler, array($this));
            }
        }
    }

    public function register($name, $handler){
        $this->services[$name] = $handler;
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
        $route = new Route($pattern, $method);
        $this->routes[] = array('route' => $route, 'handler' => $handler);
        return $route;
    }

    // register group
    public function group($pattern, \Closure $callback){
        $group = new RouteGroup($pattern);
        $this->groups[] = $group;

        // running callback in $group context
        $callback = $callback->bindTo($group);
        $callback();

        return $group;
    }

    // main process of application
    public function run(){
        // route dispatching result variables
        $route_handler = null;
        $route_params = null;
        $middlewares = array();

        // default URI will be /, default method will be GET, override by _METHOD
        $request_uri = isset($this->server['REQUEST_URI']) ? $this->server['REQUEST_URI'] : '/';
        $uri = explode('?', $request_uri);
        $method = isset($this->server['REQUEST_METHOD']) ? $this->server['REQUEST_METHOD'] : 'GET';
        if(isset($this->get['_METHOD'])) $method = $this->get['_METHOD'];
        if(isset($this->post['_METHOD'])) $method = $this->post['_METHOD'];

        // dispatch route and store result
        foreach ($this->routes as $item) {
            $route_params = $item['route']->match($uri[0], $method);
            if($route_params){
                $this->active_route = $item['route'];
                $route_handler = $item['handler'];
                $middlewares = $item['route']->getMiddlewares();
                break;
            }
        }

        // check in route groups
        if(!$route_params){
            foreach ($this->groups as $group) {
                $result = $group->match($uri[0], $method);
                if($result){
                    $this->active_route = $result['route'];
                    $route_params = $result['route_params'];
                    $route_handler = $result['handler'];
                    $middlewares = array_merge($group->getMiddlewares(), $this->active_route->getMiddlewares());
                }
            }
        }

        if(!$route_params){
            throw new Exception("No route matched", Exception::NO_ROUTE);
        }

        // run middlewares
        foreach($middlewares as $middleware){
            $response = $this->run_handler($middleware['handler'], $middleware['options']);
            if($response){
                return $response;
            }
        }

        // run route handler
        $this->register('IceAge\Route', array($this, 'get_active_route'));
        return $this->run_handler($route_handler);
    }

    public function run_handler($handler, array $params = array()){
        if(!is_callable($handler)){
            throw new Exception("Handler is not a callable", Exception::HANDLER_NOT_CALLABLE);
        }

        // get handler parameters so we can load dependencies
        $reflection = $this->getHandlerRefection($handler);
        $parameters = $reflection->getParameters();

        $services = array();
        // loop through parameters and load corresponding registered services
        foreach($parameters as $parameter) {
            $class_obj = $parameter->getClass();
            $class_name = $class_obj ? $class_obj->name : '';
            
            // try load service by its name
            $name = $parameter->getName();
            $service = isset($params[$name]) ? $params[$name] : $this->load_service($name);

            // if no service found by name, try load by its class
            if(is_null($service) && $class_name){
                $service = $this->load_service($class_name);
            }
            $services[] = $service;
        }
        return call_user_func_array($handler, $services);
    }

    public function response($response){
        if(is_string($response)){
            echo $response;
        } else {
            header('Content-Type: application/json');
            echo json_encode($response);
        }
    }

    protected function load_service($name){
        if(isset($this->services[$name]) 
            && is_callable($this->services[$name])
        ){
            $reflection = $this->getHandlerRefection($this->services[$name]);
            $parameters = $reflection->getParameters();
            $services = array();
            foreach($parameters as $parameter) {
                $class_obj = $parameter->getClass();
                $class_name = $class_obj ? $class_obj->name : '';

                $service_name = $parameter->getName();
                $service = $this->load_service($service_name);
                if(is_null($service) && $class_name){
                    $service = $this->load_service($class_name);
                }

                $services[] = $service;
            }

            return call_user_func_array($this->services[$name], $services);
        }

        return null;
    }

    private function getHandlerRefection($handler){
        $reflection = null;
        if(is_array($handler)){
            $reflection = new \ReflectionMethod($handler[0], $handler[1]);
        } else {
            try{
                $reflection = new \ReflectionFunction($handler);
            } catch (\ReflectionException $e) {
                $reflection = new \ReflectionMethod($handler);
            }
        }

        return $reflection;
    }
}
