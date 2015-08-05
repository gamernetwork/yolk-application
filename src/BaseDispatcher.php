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

use yolk\contracts\app\Dispatcher;
use yolk\contracts\app\Request;
use yolk\contracts\app\Response;

abstract class BaseDispatcher implements Dispatcher {

	/**
	 * Dependency container object.
	 * @var \yolk\contracts\app\router
	 */
	protected $router;

	/**
	 * Namespace of the leaf subclass class.
	 * @var string
	 */
	protected $namespace;

	public function __construct() {
		$class = new \ReflectionClass($this);
		$this->namespace = $class->getNamespaceName();
	}

	/**
	 * Dispatches the specified request.
	 * The optional $services parameter is passed to the constructor of handlers
	 * specified in the Object::Method format.
	 * @param Request          $request
	 * @param ServiceContainer $services
	 * @return Response
	 */
	public function dispatch( Request $request, ServiceContainer $services = null ) {

		$route = $this->router->match(
			$request->uri(),
			$request->method()
		);

		// if we have a URI prefix specified in the route then add it to the stack
		if( $prefix = isset($route['extra']['prefix']) ) {
			// we can keep adding to URI prefix to allow more than one layer of delegation
			$request->pushUriPrefix($route['extra']['prefix']);
		}

		// pass through extra info about the route
		$request->extra($route['extra']);

		// make sure we have a callable handler
		$handler = $this->makeHandler($route['handler'], $services);

		// prepend the request to the parameter array
		array_unshift($route['parameters'], $request);

		// removed for middleware work
		//$response = $this->beforeDispatch($request, $route);

		// execute the handler if we don't already have a response
		//if( !$response )
		$response = call_user_func_array($handler, $route['parameters']);

		// removed for middleware work
		//$this->afterDispatch($request, $route, $response);

		// remove the URI prefix from earlier as that application has dealt with request
		// and 'this' layer may well need to do further processing
		if( $prefix )
			$request->popUriPrefix();

		return $response;

	}

	/**
	 * Ensures the specified handler is a PHP callable.
	 * Handlers specified as a string in the format Object::Method will be
	 * converted into a callable where an instance of Object is created and
	 * passed the optional $services parameter.
	 * @param  callable|string  $handler
	 * @param  ServiceContainer $services
	 * @return callable
	 */
	protected function makeHandler( $handler, ServiceContainer $services = null ) {

		// strings in Foo::bar format are classes that need to be instantiated with the service container
		if( is_string($handler) && strpos($handler, '::') ) {

			list($class, $method) = explode('::', $handler);

			// if $class begins with a namespace separator then assumes it's a fully qualified class name
			// otherwise prefix it with the application's namespace
			if( substr($class, 0, 1) != '\\' )
				$class = "{$this->namespace}\\controllers\\{$class}";

			$handler = [new $class($services), $method];

		}

		return $handler;

	}

	/**
	 * Called prior to the request being dispatched to the handler.
	 * If a Response object is returned, the handler will be skipped.
	 * @param  Request $request
	 * @return Response|null
	 */
	public function beforeDispatch( Request $request ) {
		// do nothing by default
		return null;
		// if a response is returned, it will terminate dispatch
	}

	/**
	 * Called after the request has been dispatched to the handler.
	 * @param  Request  $request
	 * @param  Response $response
	 * @return Response|null
	 */
	public function afterDispatch( Request $request, Response $response ) {
		// MUST return a response
		return $response;
	}

}

// EOF
