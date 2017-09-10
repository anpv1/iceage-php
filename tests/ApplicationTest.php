<?php
use PHPUnit\Framework\TestCase;
use IceAge\Application;
use Psr\Http\Message\RequestInterface;

class IceAge_Application_Test extends TestCase {
    public function testDispatchNormalRoute(){
        // mock application
        $app = new Application(
            array("REQUEST_URI" => "/test", "REQUEST_METHOD" => "OPTIONS")
        );
        // mock route register
        $app->route('/test', function(){
            return 'Test';
        }, 'GET|POST|OPTIONS');
        $response = $app->run();
        $this->assertEquals($response, 'Test');
    }

    public function testDispatchRouteWithParams(){
        // mock application
        $app = new Application(
            array("REQUEST_URI" => "/hello/Bob", "REQUEST_METHOD" => "GET")
        );
        // mock route register
        $app->get('/hello/:name', function($route_params){
            return 'Hello, '.$route_params['name'];
        });
        $response = $app->run();
        $this->assertEquals($response, 'Hello, Bob');
    }

    public function testDispatchRouteWithRegex(){
        // mock application
        $app = new Application(
            array("REQUEST_URI" => "/test/100", "REQUEST_METHOD" => "GET")
        );
        // mock route register
        $app->get('/test/:param|[a-zA-Z]+|', function($route_params){
            return 'String:'.$route_params['param'];
        });
        $app->get('/test/:param|[0-9]+|', function($route_params){
            return 'Number:'.$route_params['param'];
        });
        $response = $app->run();
        $this->assertEquals($response, 'Number:100');
    }

    public function testDispatchRouteWithOverride(){
        // mock application
        $app = new Application(
            array("REQUEST_URI" => "/test", "REQUEST_METHOD" => "GET"),
            array("_METHOD" => 'POST')
        );
        // mock route register
        $app->post('/test', function($route_params){
            return 'OK';
        });
        $response = $app->run();
        $this->assertEquals($response, 'OK');
    }

    /**
     * @expectedException     \IceAge\Exception
     * @expectedExceptionCode 1
     */
    public function testNoRouteMatch(){
        // mock application
        $app = new Application(
            array("REQUEST_URI" => "/test", "REQUEST_METHOD" => "GET")
        );
        // mock route register
        $app->post('/test', function($route_params){
            return 'OK';
        });
        $response = $app->run();
    }

    /**
     * @expectedException     \IceAge\Exception
     * @expectedExceptionCode 2
     */
    public function testHandlerConfigError(){
        // mock application
        $app = new Application(
            array("REQUEST_URI" => "/test", "REQUEST_METHOD" => "GET")
        );
        // mock route register
        $app->get('/test', 'NoFunction::InThisContext');
        $response = $app->run();
    }

    public function testCoreServices(){
        // mock application
        $app = new Application(
            array("REQUEST_URI" => "/hello/Bob", "REQUEST_METHOD" => "GET"),
            array('id' => 123)
        );
        // mock route register
        $app->get('/hello/:name', function($route_params, RequestInterface $request){
            $query = $request->getQueryParams();
            return 'Name:'.$route_params['name'].'. ID:'.$query['id'];
        });
        $response = $app->run();
        $this->assertEquals($response, 'Name:Bob. ID:123');
    }

    public function testBootstrapWithRouteAndService(){
        // mock application
        $app = new Application(
            array("REQUEST_URI" => "/hello", "REQUEST_METHOD" => "POST")
        );
        // mock route register in bootstrap function
        $registerRoute = function($app){
            $app->post('/hello', function($world){
                return 'Hello, '. $world.'!';
            });
        };
        $registerService = function($app){
            $app->register('world', function(){
                return 'world';
            });
        };
        // bootstrap and run
        $app->bootstrap(array($registerRoute, $registerService));
        $response = $app->run();
        $this->assertEquals($response, 'Hello, world!');
    }

    public function testMiddlewareAllowContinue(){
        // mock application
        $app = new Application(
            array("REQUEST_URI" => "/test/0", "REQUEST_METHOD" => "GET")
        );
        // mock route register
        $app->get('/test:stop|\d+', function(){
            return 'Test';
        })->middleware(function($stop, $route_params){
            // only stop if params in route and params of middleware equal 1
            if($route_params['stop'] == 1 && $stop == 1) return 'Stop';
        }, array('stop' => 1));
        $response = $app->run();
        $this->assertEquals($response, 'Test');
    }

    public function testMiddlewareReturnImmediately(){
        // mock application
        $app = new Application(
            array("REQUEST_URI" => "/test", "REQUEST_METHOD" => "GET")
        );
        // mock route register
        $app->get('/test', function(){
            return 'Test';
        })->middleware(function($stop){
            if($stop) return 'Stop';
        }, array('stop' => 1));
        $response = $app->run();
        $this->assertEquals($response, 'Stop');
    }
}
