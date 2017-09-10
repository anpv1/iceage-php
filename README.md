# IceAge -  micro php framework
IceAge is a PHP nano-framework which is lightning fast and extreme small with total around 250 lines of code. IceAge provide a very simple, but powerful and flexible interface for building web-based applications.

In my Lenovo U4170, Intel Core i5 5200U, 4GB RAM, using apache bench, a full page using IceAge with dotenv to load environment variables, Twig for template engine and 1 PDO MySQL query can reach 1639.09 requests/second. A hello world page using IceAge can reach up to 10167.46 requests/second.

### Hello, world!
```php
<?php
// index.php
// composer autoload, install any dependencies you need
require_once('../vendor/autoload.php');
$app = new \IceAge\Application();

// routes definition
$app->get('/', function(){return 'Hello, world!';});
$result = $app->run();
$app->response($result);

```

### Routing
```php
// Routing with regex definition of parameters and multiple methods
// id in URL must be digit to match
$app->route(
    '/hello/:id|[0-9]+|', 
    '\\App\\Controller\\Hello::get', 
    'GET|POST'
);
// optional parameters
// this will match /blog/2017, /blog/2017/07, /blog/2017/07/01
$app->get('/blog(/:year|[\d]{4}|(/:month|[\d]{2}|(/:day|[\d]{2}|)?)?)?', function($route_params){
    return $route_params;
});
```

### Using Services
IceAge support 2 main features: routes and services management. You can register service to the application using register method:
```php
<?php
// index.php
// composer autoload, install any dependencies you need
require_once('../vendor/autoload.php');
use \Twig_Environment as Twig;
$app = new \IceAge\Application();

// register $db service
$app->register('db', function(){
    return new \PDO(
        $_ENV['DB_DSN'], 
        $_ENV['DB_USER'], 
        $_ENV['DB_PASSWORD'],
        array(
            \PDO::ATTR_PERSISTENT => true
        )
    );
});

// register Twig_Environment template
$app->register('Twig_Environment', function(){
    $loader = new \Twig_Loader_Filesystem(realpath('app/templates/views'));
    return new Twig($loader, array(
        'cache' => realpath('app/templates/cache'),
        'auto_reload' => true
    ));
});

// routes definition
// in the route handler you can use the $db service which is a PDO instance
// and load any parameter name which is a Twig_Environment instance
$app->get('/', function($db, Twig $template){
    return $template->render('template.html', array('message' => 'Hello, world!'));
});

$result = $app->run();
$app->response($result);

```
As you can see in the above example, services can be loaded by its name ($db) or by its class name (Twig_Environment)
### Bootstrap application
IceAge application object support a bootstrap method which can use to register all services and routes handler. For example:
```php
<?php
// public/index.php
chdir(getcwd().'/../');
// composer autoload, install any dependencies you need
require_once('vendor/autoload.php');

$app = new \IceAge\Application();
$app->bootstrap(array(
    '\\App\\Bootstrap\\Env::load',
    '\\App\\Bootstrap\\Services::register',
    '\\App\\Bootstrap\\Routes::register'
));

try {
    $result = $app->run();
}
catch(Exception $e){
    $response = $app->run_handler('\\App\\Controller\\Error::error', array('error' => $e));
    $result = $app->response($response);
}

$app->response($result);

```

```php
<?php
// app/Bootstrap/Env.php
namespace App\Bootstrap;
use Rfussien\Dotenv\Loader;

class Env {
    public static function load(){
        $dotenv = new Loader('app/');
        $dotenv->load();
    }
}

```

```php
<?php
// app/Bootstrap/Routes.php
namespace App\Bootstrap;

class Routes {
    public static function register($app){
        // routes definition
        $app->get('/', '\\App\\Controller\\Index::get');
        $app->get('/login', '\\App\\Controller\\Login::index');
        $app->route(
            '/hello/:name/:id', 
            '\\App\\Controller\\Hello::get', 
            'GET|POST'
        );
        $app->get('/group/get/:id', '\\App\\Controller\\Group::get');
        $app->post('/user/signin', '\\App\\Controller\\Login::signin');
    }
}

```

```php
<?php
// app/Bootstrap/Services.php
namespace App\Bootstrap;

class Services {
    private static $dbh;
    private static $twig;
    private static $acl;

    public static function register($app){
        $app->register('db', '\\App\\Bootstrap\\Services::db_service');
        $app->register('twig', '\\App\\Bootstrap\\Services::twig_service');
    }

    public static function db_service(){
        if(!self::$dbh){
            self::$dbh = new \PDO(
                $_ENV['DB_DSN'], 
                $_ENV['DB_USER'], 
                $_ENV['DB_PASSWORD'],
                array(
                    \PDO::ATTR_PERSISTENT => true
                )
            );
        }
        return self::$dbh;
    }

    public static function twig_service(){
        if(!self::$twig){
            $loader = new \Twig_Loader_Filesystem(realpath('app/templates/views'));
            self::$twig = new \Twig_Environment($loader, array(
                'cache' => realpath('app/templates/cache'),
                'auto_reload' => true
            ));
        }

        return self::$twig;
    }
}

```

```php
<?php
// app/Controller/Hello.php
namespace App\Controller;

use App\DataTable\TestTbl;

class Hello
{
    // $route_params is a special service to get the parameters defined on route
    // In this case the route is /hello/:name/:id
    // So if the request URL is /hello/Bob/1 then $route_params['name'] = "Bob"
    // $route_params['id'] = 1
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
// app/Controller/Error.php
namespace App\Controller;

class Error {
    // $error is passed from the below line in index.php
    // $response = $app->run_handler('\\App\\Controller\\Error::error', array('error' => $e));
    public static function error($twig, $error){
        return $twig->render('error/index.html', array('error' => $error, 'debug' => $_ENV['DEBUG_MODE']));
    }
}

```
### Request lifecycle
* The bootstrap functions is call, with instance of IceAge Application object
* The application object will try to find the matched routes
* If no route matched, an IceAge\Exception is throw with the code is IceAge\Exception::NO_ROUTE and exit
* If one route matched, IceAge Application will run the route middlewares if exits.
* If the route middleware handler run and return the response, IceAce Application will move the the output process step without running route handler.
* If the route middleware does not return value, IceAge Application load all services which the route handler used, and then call route handler with loaded services and then get the response.
* Depending on the route handler response:
  * If it is a string, IceAge send the string as output to browser immediately.
  * If it is an instance of \Psr\Http\Message\ResponseInterface, IceAge use \Zend\Diactoros to produce the output and send to browser.
  * In other cases, IceAge use json_encode function to produce the output and send it with header Content-type: application/json.
  
To register route middleware, you can use middleware method of the route object:
```php
<?php
$app->get('/', '\\App\\Controller\\Index::get')
    // middleware handler can use any registered services and params passed on
    ->middleware(function($db, $permission){
        // $permission = "admin"
        // implement middleware here
    },
    array('permission' => 'admin'))
    ->middleware(function($login_required){
        // $login_required = True
        // implement middleware here
    },
    array('login_required' => True));
    
```
