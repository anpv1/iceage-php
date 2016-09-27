# IceAge -  micro php framework
IceAge is a PHP micro-framework which is true stunningly fast and extreme small (less than 300 lines of code, including comments). IceAge provide a very simple, but powerful and flexible interface for building web-based applications. If you love Slim Middleware and Laravel Service Container, you may love the more simpler way doing it in IceAge. Give it 5 minutes and maybe you will love IceAge, too.

In my Lenovo U4170, Intel Core i5 5200U, 4GB RAM, using apache bench, a full page using IceAge with Twig and 1 PDO MySQL query reach 2043.40 requests/second, while a hello world page using Slim 3 reach 1420.32 requests/second and hello world page using Lumen reach 988.65 requests/second. A hello world page using IceAge can reach up to 11187.74 requests/second. It is true stunningly fast, right?

### Getting started
```php
<?php
// composer autoload, install any dependencies you need
require_once('../vendor/autoload.php');
$app = new \IceAge\Application();

// routes definition
$app->get('/', function(){return 'Hello, world!'});

$app->run();
```

### Routing

### Services registering and dependency injection

### Middlewares
