<?php
use PHPUnit\Framework\TestCase;
use IceAge\Route;

class IceAge_Route_Test extends TestCase {
    public function testNormalRouteMatch(){
        $route = new Route('/', 'GET');
        $this->assertGreaterThan(0, count($route->match('/', 'GET')));
        $this->assertCount(0, $route->match('/hello', 'GET'));
        $this->assertCount(0, $route->match('/', 'POST'));
    }

    public function testParameterMatch(){
        $route = new Route('/hello/:name', 'GET');
        $params = $route->match('/hello/Bob', 'GET');
        $this->assertArrayHasKey('name', $params);
        $this->assertEquals($params['name'], 'Bob');
    }

    public function testMultipleMethodsMatch(){
        $route = new Route('/hello', 'GET|POST');
        $this->assertGreaterThan(0, count($route->match('/hello', 'GET')));
        $this->assertGreaterThan(0, count($route->match('/hello', 'POST')));
        $this->assertCount(0, $route->match('/hello', 'PUT'));
    }

    public function testOptionalParameterMatch(){
        $route = new Route('/blog(/:year(/:month(/:day)?)?)?', 'GET');
        $this->assertGreaterThan(0, count($route->match('/blog', 'GET')));
        $this->assertGreaterThan(0, count($route->match('/blog/2017', 'GET')));
        $this->assertGreaterThan(0, count($route->match('/blog/2017/07', 'GET')));
        $this->assertGreaterThan(0, count($route->match('/blog/2017/07/01', 'GET')));

    }

    public function testRegexParameterMatch(){
        $route = new Route('/post/:id|\d+|', 'GET');
        $this->assertGreaterThan(0, count($route->match('/post/1', 'GET')));
        $this->assertGreaterThan(0, count($route->match('/post/32', 'GET')));
        $this->assertCount(0, $route->match('/post/test', 'GET'));
        $this->assertCount(0, $route->match('/post/1a', 'GET'));
    }

    public function testGenerate(){
        $route = new Route('/post/:id|\d+|', 'GET');
        $url = $route->generate(array('id' => 5));
        $this->assertEquals($url, '/post/5');

        $route = new Route('/blog/(:year|\d{4}|(/:month|d{2}|(/:day|\d{2}|)?)?)?', 'GET');
        $url = $route->generate(array('year' => '2017'));
        $this->assertEquals($url, '/blog/2017');

        $url = $route->generate(array('year' => '2017', 'month' => '07'));
        $this->assertEquals($url, '/blog/2017/07');

        $url = $route->generate(array('year' => '2017', 'day' => '01'));
        $this->assertEquals($url, '/blog/2017');

        $url = $route->generate(array('year' => '2017', 'month' => '07', 'day' => '01'));
        $this->assertEquals($url, '/blog/2017/07/01');
    }
}
