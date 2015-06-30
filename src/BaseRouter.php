<?php
/*
 * This file is part of Yolk - Gamer Network's PHP Framework.
 *
 * Copyright (c) 2015 Gamer Network Ltd.
 * 
 * Distributed under the MIT License, a copy of which is available in the
 * LICENSE file that was bundled with this package, or online at:
 * https://github.com/gamernetwork/yolk-application
 */

namespace yolk\app;

/**
 * Routes are used to determine the controller and action for a requested URI.
 * A route is a regular expression that is used to match a URI to a handler,
 * bracketed groups are used to define the parameters that are passed to the handler.
 *
 * Handlers can be anything as they are interpreted by the client, 
 * however in most cases they will be a valid PHP callable:
 * * Class   - array('class_name', 'method')
 * * Object  - array($object, 'method')
 * * Object  - $object -- via __invoke()
 * * Closure - function()
 * Yolk Application instances can also use a simple string in the form
 * ClassName/method - the application will create the class passing in the
 * service container and then call "method".
 * 
 * Examples:
 * $router = new \yolk\app\Router();
 *
 * // Should create an instance of StaticController and call the 'about' method.
 * $router->addRoute('/about', 'StaticController/about');
 *
 * // Should call the static method 'about' on ProfileController class.
 * $router->addRoute('/users/(\d+)/profile', ['ProfileController', 'about']);
 *
 * // Closure callback
 * $router->addRoute('/articles/(\d+)', function( $id ) {
 *    $article_name = CMS::getArticleName($id);
 *    header("Location: /articles/{$article_name}"))
 * );
 *
 * Regexs are a good way of performing simple validation without have to instantiate the entire
 * application stack. In the example above any request for an article where the article id isn't
 * a numeric value won't match and will generate a 404 response.
 *
 * You can also specify the HTTP methods to match on by prepending them to the regex. Methods should be
 * enclosed in brackets, separated by pipes and have a semi-colon after the closing bracket:
 * Only allow HTTP GET method:
 * $route->addRoute('GET:/about', 'StaticController/about')
 *
 * Allow HTTP GET, POST and DELETE methods:
 * $route->addRoute('(GET|POST|DELETE):/about', 'StaticController/about')
 */
class BaseRouter {

	/**
	 * Array of routes that have been registered.
	 * @var array
	 */
	protected $routes;

	/**
	 * Add a route.
	 * @param bool  $regex    regular expresion that is matched against a request URI.
	 * @param mixed $handler  the handler to associate with the route.
	 * @param mixed $extra    extra data to be supplied to action not encoded in query.
	 */
	public function addRoute( $regex, $handler, $extra = [] ) {

		$methods = [];

		// if allowed methods are specified then find out what they are
		if( preg_match('/^\([a-Z|]+\):(.*)/i', $regex, $m) ) {
			$methods = explode('|', trim($m[1], '()'));
			$regex   = $m[2];
		}

		$this->routes[$regex] = array(
			'methods' => $methods,
			'handler' => $handler,
			'extra'   => $extra,
		);

	}

	/**
	 * Turn a controller action into a URL
	 * @param string $action      controller action spec (like JobsController/index)
	 * @param array  $args        positional arguments for url (e.g. job id) as strings
	 */
	public function reverse( $handler, $args = [] ) {
		foreach( $this->routes as $route ) {
			if( $route['handler'] == $handler ) {
				$url = $route['url'];
				foreach( $args as $a ) {
					$url = preg_replace( '/\([^\)]*\)/', $a, $url );
				}
				return $url;
			}
		}
		throw new \Exception('Reverse not found');
	}

	/**
	 * Find a route that matches the specified Request.
	 * @param string $uri      URI to be matched.
	 * @param string $method   HTTP method the URI request was made with.
	 * @return array|false     an array containing the handler, parameters and extra data defined by the route
	 */
	public function match( $uri, $method = 'GET' ) {

		$route      = false;
		$parameters = [];

		// routes that don't use parameters should match directly
		if( isset($this->routes[$uri]) ) {
			$route = $this->routes[$uri];
		}
		else {
			// try and match the uri against a defined route
			foreach( $this->routes as $regex => $spec ) {
				if( preg_match(";{$regex};", $uri, $parameters) ) {
					$route = $spec;
					array_shift($parameters);  // first element is the complete string, we only care about the sub-matches
					break;
				}
			}
		}

		if( !$route )
			throw new exceptions\NotFoundException();

		// check methods
		if( $route['methods'] && !in_array($method, $route['methods']) )
			throw new exceptions\NotAllowedException();

		return [
			'handler'    => $route['handler'],
			'parameters' => $parameters,
			'extra'      => $route['extra'],
		];

	}

}

// EOF