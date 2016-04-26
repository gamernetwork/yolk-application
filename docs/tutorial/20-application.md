# Application Class

Application classes will usually extend `BaseApplication`:

```php
namespace myapp;

use yolk\app\BaseApplication;

class MyApplication extends BaseApplication {

}
```

## Services

The first thing our application needs is a `ServiceContainer` to handle our dependency
injections requirements. `BaseApplication` defines a `loadServices()` method that loads
the default Yolk service implementations. Extending classes may optionally override
this to define additional services:

```php
public function loadServices() {

  // create a default service container and load Yolk services
  parent::loadServices();

  // via a ServiceProvider
	// $this->services->register(
	//   new \myapp\ServiceProvider()
	// );

  // directly defined
  // $this->services['my-service'] = function( $container ) {
	// 	return new MyService();
	// };

}
```

## Configuration

The `loadConfig()` method is responsible for loading the applications configuration
into the `config` service; with the default implementation loading data from the
file ``config/main.php`

```php
// grab the current setting of the Yolk debug flag
$config['debug'] = yolk\Yolk::isDebug();

$config['app'] = [
	'name'     => 'My First App',
];

// this blocks defines the directory structure of the application
// by changing the values here it's possible to relocate various
// aspects of the application; for example, changing the tmp directory
// to be /tmp/myapp which exists as an in-memory partition
$config['paths'] = [

  // only used for app's that exist under a sub-directory on a domain
	'web'     => '',

	// where are all the static web assets
	'static'  => '/static',

	// root directory of the app
	'app'     => realpath(__DIR__. '/..'),

	// location of view templates
	'view'    => realpath(__DIR__. '/../app/views'),

	// location of temp files
	'tmp'     => realpath(__DIR__. '/../tmp'),

];

$config['databases'] = [
	'main' => 'mysql://localhost/myapp?charset=utf8',
];

// configuration for Twig
$config['views.twig'] = [
	'adapter'       => 'twig',
	'cache_path'    => $config['paths']['tmp']. "/cache/views",
	'debug'         => $config['debug'],
	'view_path'     => $config['paths']['view'],
];

// define a debug log that uses the default PHP error log
$config['logs']['debug'] = 'php';

// include local config file if one exists
file_exists(__DIR__. '/local.php') && include(__DIR__. '/local.php');
```

## Routes

`BaseApplication` defines the abstract method `loadRoutes()` that
extending classes should implement to define the application's routes.

Our tutorial application is a 

```php
public function loadRoutes() {

  // create a new Router instance
  $router = $this->services['router'];

  // define some routes
  $router->addRoute("/$", "BlogController::homepage");
  $router->addRoute("/posts/([\d]+{4})/(.*)$", "BlogController::viewPost");
 
  $router->addRoute("/admin$", "AdminController::dashboard");
  $router->addRoute("GET:/admin/edit/([\d]+)?$", "AdminController::newPost");
  $router->addRoute("POST:/admin/save/([\d]+)?$", "AdminController::savePost");

  // make the Router available to the application
  $this->router = $router;

}
```
