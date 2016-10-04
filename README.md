# IceAge -  micro php framework
IceAge is a PHP micro-framework which is lightning fast and extreme small (less than 300 lines of code, including comments). IceAge provide a very simple, but powerful and flexible interface for building web-based applications. If you love Slim Middleware and Laravel Service Container, you may love the more simpler way doing it in IceAge. Give it 5 minutes and maybe you will love IceAge, too.

In my Lenovo U4170, Intel Core i5 5200U, 4GB RAM, using apache bench, a full page using IceAge with Twig and 1 PDO MySQL query reach 2043.40 requests/second, while a hello world page using Slim 3 reach 1420.32 requests/second and hello world page using Lumen reach 988.65 requests/second. A hello world page using IceAge can reach up to 8304.73 requests/second. It is true stunningly fast, right?

### Getting started
```php
<?php
// composer autoload, install any dependencies you need
require_once('../vendor/autoload.php');
$app = new \IceAge\Application();

// routes definition
$app->get('/', function(){return 'Hello, world!';});
$app->run();

```

### Routing
IceAge have the similar routing system like other micro framework, which support *GET|POST|PUT|DELETE*
```php
<?php
// static function supported
$app->get('/categories', '\HelloApp\Controller\Category::list');
// route params supported, must use exactly the name $route_params in callback function
// to get the route parameters
$app->get('/category/:id', function($route_params){return 'Get category '.$route_params['id'];});
$app->post('/category', function(){return 'Create category';});
$app->put('/category/:id', function($route_params){return 'Update category '.$route_params['id'];});
$app->delete('/category/:id', function($route_params){return 'Delete category '.$route_params['id'];});
// multiple methods support
$app->route('/hello/:name/:id', '\\HelloApp\\Controller\\Hello::get', 'GET|POST');

```

IceAge also support for method overwrite, with the value of *$_REQUEST['_METHOD']* if this parameter is set. Please make sure that you set the uppercase value (*GET|POST|PUT|DELETE*) for this value.

### Services registering and dependency injection
IceAge allow you to define all available services using in your application and how to setup it
```php
// services definition if any
$services = array(
    'db' => array(
        'handler' => '\\HelloApp\\Middlewares\\Pdo::setup',
        'options' => array(
            'dsn' => 'mysql:host=localhost;dbname=lightning_php', 
            'user' => 'db_user', 
            'password' => 'dB_PaSSwoRd',
            'options' => array(
                \PDO::ATTR_PERSISTENT => true
            )
        )
    ),
    'twig' => array(
        'handler' => '\HelloApp\Middlewares\Twig::setup',
        'options' => array(
            'loader' => 'Twig_Loader_Filesystem',
            'loader_options' => realpath('../hello_app/templates/views'),
            'env_options' => array(
                'cache' => realpath('../hello_app/templates/cache'),
                'auto_reload' => true
            )
        )
    )
);
$app->services($services);

```
Example of PDO service
```php
<?php
namespace HelloApp\Services;

class Pdo
{
    public static function setup($options)
    {
        return new \PDO(
            $options['dsn'], 
            $options['user'], 
            $options['password'], 
            $options['options']
        );
    }
}

```
Example of Twig service
```php
<?php
namespace HelloApp\Services;

class Twig {
    public static function setup($options){
        $loader_class = $options['loader'];
        $loader = new $loader_class($options['loader_options']);
        return new \Twig_Environment($loader, $options['env_options']);
    }
}

```
To use a service in a routing callback, just pass the service name to the handler parameters and IceAge will automatically call the setup function and pass the service instance to handler function for you.
```php
<?php
namespace HelloApp\Controller;

use \HelloApp\DataTable\TestTbl;

class Hello
{
    public static function get($twig, $db, $route_params)
    {
        $test_tbl = new TestTbl($db);
        $row = $test_tbl->get($route_params['id']);

        return $twig->render('hello/index.html', array('row' => $row));
    }
}

```
```php
<?php
namespace HelloApp\DataTable;

class TestTbl {
    protected $_name = 'test_tbl';

    public function __construct(\PDO $dbh){
        $this->dbh = $dbh;
    }

    public function get($id){
        $sql = "SELECT * FROM `{$this->_name}` WHERE `id` = ?";
        $stmt = $this->dbh->prepare($sql);
        $stmt->execute(array($id));

        return $stmt->fetchObject();
    }
}

```
Remember that the function *$app->services()* is just use to define available services, not to setup all of the service. Only needed services will be setup for each routing request handler to optimize performance.

You may wonder that is *$route_params* is a service or not? The answer is yes, *$route_params* is the only one service defined by IceAge core.

### Middlewares
Middlewares in IceAge is use to setup common function which can use to redirect the user, add params, or change the routing handler response... There are 2 types of middlewares in IceAge, global middlewares which run for all routing requests, and local middlewares which run only for a specific routing request.
