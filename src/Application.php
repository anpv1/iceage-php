<?php
namespace IceAge;

class Application
{
    private static $request;
    protected $routes = array();
    protected $services = array();
    protected $server;
    protected $get;
    protected $post;
    protected $cookie;
    protected $files;

    public function __construct(array $server = array(), array $get = array(), 
        array $post = array(), array $cookie = array(), array $files = array())
    {
        $this->server = $server ? $server : $_SERVER;
        $this->get = $get ? $get : $_GET;
        $this->post = $post ? $post : $_POST;
        $this->cookie = $cookie ? $cookie : $_COOKIE;
        $this->files = $files ? $files : $_FILES;
        $this->register('Psr\Http\Message\RequestInterface', '\\IceAge\\Application::psr_request');
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
        $route = new Route($pattern, 'GET');
        $this->routes[] = array('route' => $route, 'handler' => $handler);
        return $route;
    }
    
    // shortcut for "POST" route
    public function post($pattern, $handler){
        $route = new Route($pattern, 'POST');
        $this->routes[] = array('route' => $route, 'handler' => $handler);
        return $route;
    }

    // shortcut for "PUT" route
    public function put($pattern, $handler){
        $route = new Route($pattern, 'PUT');
        $this->routes[] = array('route' => $route, 'handler' => $handler);
        return $route;
    }

    // shortcut for "DELETE" route
    public function delete($pattern, $handler){
        $route = new Route($pattern, 'DELETE');
        $this->routes[] = array('route' => $route, 'handler' => $handler);
        return $route;
    }

    // register a route
    public function route($pattern, $handler, $method = 'GET|POST'){
        $route = new Route($pattern, $method);
        $this->routes[] = array('route' => $route, 'handler' => $handler);
        return $route;
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
                $route_handler = $item['handler'];
                $middlewares = $item['route']->getMiddlewares();
                break;
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
        return $this->run_handler($route_handler, array('route_params' => $route_params));
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
        } else if($response instanceof \Psr\Http\Message\ResponseInterface){
            // PSR-7 support
            $this->psr_response($response);
        } else {
            header('Content-Type: application/json');
            echo json_encode($response);
        }
    }

    protected static function psr_request(){
        if(!self::$request){
            self::$request = \Zend\Diactoros\ServerRequestFactory::fromGlobals(
                                $this->server, $this->get, $this->post,
                                $this->cookie, $this->files
                            );
        }
        return self::$request;
    }

    protected function load_service($name){
        if(isset($this->services[$name]) 
            && is_callable($this->services[$name])
        ){
            $reflection = $this->getHandlerRefection($this->services[$name]);
            $parameters = $reflection->getParameters();
            $services = array();
            foreach($parameters as $parameter) {
                $service_name = $parameter->getName();
                $services[] = $this->load_service($service_name);
            }

            return call_user_func_array($this->services[$name], $services);
        }

        return null;
    }

    // this code come from zend-diactoros
    private function psr_response($response){
        $emitter = new \Zend\Diactoros\Response\SapiEmitter();
        $emitter->emit($response);
    }

    private function getHandlerRefection($handler){
        $reflection = null;
        try{
            $reflection = new \ReflectionFunction($handler);
        } catch (\ReflectionException $e) {
            $reflection = new \ReflectionMethod($handler);
        }

        return $reflection;
    }
}
