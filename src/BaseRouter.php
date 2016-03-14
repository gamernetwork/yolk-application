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

use yolk\contracts\app\Router;

/**
 * Routes are used to determine the controller and action for a requested URI.
 * A route is a regular expression that is used to match a URI to a handler,
 * bracketed groups are used to define the parameters that are passed to the handler.
 *
 * Handlers can be anything as they are interpreted by the client. 
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
class BaseRouter implements Router {

	/**
	 * Array of routes that have been registered.
	 * @var array
	 */
	protected $routes;

	public function __construct() {
		$this->routes = [];
	}

	public function getRoutes() {
		return $this->routes;
	}

	public function addRoute( $regex, $handler, $extra = [] ) {

		$methods = [];

		// if allowed methods are specified then find out what they are
		if( preg_match('/^\(([A-z|]+)\):(.*)/i', $regex, $m) ) {
			$methods = explode('|', trim($m[1], '()'));
			$regex   = $m[2];
		}

		$this->routes[] = array(
			'pattern' => $regex,
			'methods' => $methods,
			'handler' => $handler,
			'extra'   => $extra,
		);

	}

	public function reverse( $handler, $args = [] ) {
		foreach( $this->routes as $route ) {
			if( $route['handler'] == $handler ) {
				$url = $route['url'];
				foreach( $args as $a ) {
					$url = preg_replace('/\([^\)]*\)/', $a, $url);
				}
				return $url;
			}
		}
		throw new \Exception('Reverse not found');
	}

	public function test( $route, $uri, $method ) {

		$parameters = [];
		$pattern = $route['pattern'];

		if( preg_match(";{$pattern};", $uri, $parameters) ) {

			// HTTP verb check
			if( $route['methods'] && !in_array($method, $route['methods']) ) {
				return false;
			}

			// looks good
			// first element is the complete string, we only care about the sub-matches
			array_shift($parameters); 

			return $parameters;

		}

		return false;

	}

}

// EOF
