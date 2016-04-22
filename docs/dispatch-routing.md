# Dispatch and Routing

A `Dispatcher` accepts a `Request` and matches the request's uri against routes
defined in a `Router`.

If a match is found, the `Request` is passed to the handler defined for that route,
passing through any defined [middleware](docs/middleware.md). If no match is found, false is returned.

Handlers can be any PHP `callable` but are usually instances of classes extending
`BaseController`.

`BaseDispatcher` provides basic routing, dispatch and middleware functionality.
It is declared `abstract` and subclasses are responsible for providing valid
[`ServiceContainer`](docs/services.md) and `Router` instances.

`BaseApplication` and `BaseModule` are extensions of `BaseDispatcher`.

```php
use yolk\contracts\app\Request;
use yolk\app\BaseRouter;
use yolk\app\BaseDispatcher;

// BaseDispatcher is abstract, subclasses are responsible for providing a Router instance
class MyDisptacher extends BaseDispatcher {

	public function __construct() {

		$router = new BaseRouter();

		// define a new route
		$router->addRoute(

			// first argument is the regular expression that uris must match
			// bracketed expressions are passed to the handler as arguments in the order they appear
			'/users/([0-9]+)/(edit|view|delete)$',

			// second argument is a callable that accepts the request and generates a response
			function( Request $request, $id, $action ) {
				// generate and return a response
			}

		);
	
	}

}

$dispatcher = new MyDispatcher();

$dispatcher->dispatch($request);
```
