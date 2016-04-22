# Middleware

Middlware provide a mechanism for filtering requests entering an application
and modifying responses before being sent to the client.
For example, Yolk provides a simple middleware to handle "flash" messages and
to inject the profiler data into a response.

Middleware can be used for a variety of purposes including authentication,
authorisation, loggings, CSRF protection, etc.

## Defining Middleware

A middleware is simiply a PHP `callable` that is added to a [`Dispatcher`](dispatch-routing.md).
Middleware should except two parameters, the first being the [`Request`](request-response.md),
the second being the next middleware (`closure`) in the chain.

A simple middleware could be a closure:

```php
$dispatcher->addMiddleware(
	function( Request $request, callable $next ) {

		// actions taken before the request is handled
		// e.g. authentication/authorisation

		// execute the next middleware
		$response = $next($request);

		// actions taken after the request is handled
		// e.g. logging, saving data/data

		return $response;

	}
);
```

## Chaining Middleware

Middleware added to a `Dispatcher` forms a queue, subsequent middleware being placed on the end of the queue.
When a request is dispatched, middleware is executed in order. A middleware may or may not call the next
middleware in the queue, it must however return a valid `Response`.

For example, a middleware that handles authentication would not want to allow the request to continue if the
provided credentials were invalid; it could therefore return a `401 Unauthorized` response.
Any subsequent middlewares would be executed, nor would the `Request` reach the handler assigned to the URI.
