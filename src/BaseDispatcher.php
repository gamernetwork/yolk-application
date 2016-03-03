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
use yolk\contracts\app\Middleware;
use yolk\contracts\app\Request;
use yolk\contracts\app\Response;
use yolk\contracts\app\Controller;

/**
 * A dispatcher is anything that accepts a request and tries to do something with it!
 * Dispatchers will usually invoke a router in order to map URIs to actions.
 *
 * Use by subclassing, rather than directly.
 *
 * Actions can be a valid PHP callable:
 *
 * * Class   - array('class_name', 'method')
 * * Object  - array($object, 'method')
 * * Object  - $object -- via __invoke()
 * * Closure - function()
 *
 * ...or a string representing a controller class and method the dispatcher will
 * either look up in the controller registry (the service container) or
 * instantiate a controller if existing one isn't found.
 * 
 * // Should create an instance of StaticController and call the 'about' method.
 * $this->router->addRoute('/about', 'StaticController::about');
 *
 * // Should call the static method 'about' on ProfileController class.
 * $this->router->addRoute('/users/(\d+)/profile', ['ProfileController', 'about']);
 *
 * // Closure callback
 * $router->addRoute('/articles/(\d+)', function( $id ) {
 *    $article_name = CMS::getArticleName($id);
 *    header("Location: /articles/{$article_name}"))
 * );
 *
 */
abstract class BaseDispatcher implements Dispatcher, Middleware {

	/**
	 * Dependency container object.
	 * Must be set by the extending class.
	 * @var ServiceContainer
	 */
	protected $services;

	/**
	 * Router.
	 * Must be set by the extending class.
	 * @var \yolk\contracts\app\router
	 */
	protected $router;

	/**
	 * Namespace of the leaf subclass class.
	 * @var string
	 */
	protected $namespace;

	/**
	 * Array of callables
	 * @var [type]
	 */
	protected $middleware;

	public function __construct() {
		$class = new \ReflectionClass($this);
		$this->namespace  = $class->getNamespaceName();
		$this->middleware = [];
	}

	/**
	 * Register a middleware with the dispatcher.
	 * Middleware's are callables that accept a Request object and a next middleware
	 * as parameters.
	 * @param callable $middleware
	 */
	public function addMiddleware( callable $middleware ) {
		$this->middleware[] = $middleware;
	}

	public function __invoke( Request $request, callable $next = null ) {

		// not initialised yet so should probably do that
		if( empty($this->services) )
			$this->init();

		// grab the next middleware from the queue
		$middleware = array_shift($this->middleware);

		// we've reached the end of the middleware queue, so dispatch the request
		// to it's endpoint
		if( empty($middleware) )
			return $this->dispatch($request);

		// run the next middleware
		return $middleware($request, $this);

	}

	/**
	 * Dispatches the specified request.
	 * @param Request          $request
	 * @return Response
	 */
	public function dispatch( Request $request ) {

		foreach( $this->router->getRoutes() as $route ) {

			if( false !== $parameters = $this->router->test(
				$route,
				$request->uri(),
				$request->method()
			) ) {

				// if we have a URI prefix specified in the route then add it to the stack
				if( $prefix = isset($route['extra']['prefix']) ) {
					// we can keep addig to URI prefix to allow more than one layer of delegation
					$request->pushUriPrefix($route['extra']['prefix']);
				}

				// pass through extra info about the route
				$request->extra($route['extra']);

				// prepend the request to the parameter array
				array_unshift($parameters, $request);

				// make sure we have a callable handler
				$handler = $this->makeHandler($route['handler']);

				// execute the handler
				$response = call_user_func_array($handler, $parameters);

				// remove the URI prefix from earlier as that application has dealt with request
				// and 'this' layer may well need to do further processing
				if( $prefix )
					$request->popUriPrefix();

				if( $response !== false ) {
					// we handled it bro
					return $response;
				}
				// we didn't handle it for whatever reasons so drop through!
			}
		}
		// nothing handled this for whatever reason (no route or dispatcher matched,
		// or the handlers decided to give up)
		return false;
	}

	/**
	 * Initialisation function called when $this->services is empty.
	 * This should really only be on the first call to __invoke(), assuming it
	 * hasn't been called explicitly before then.
	 * This function should set the services and router properties.
	 * @return void
	 */
	abstract protected function init();

	/**
	 * Ensures the specified handler is a PHP callable.
	 * Handlers specified as a string in the format Object::Method will be
	 * converted into a callable where an instance of Object is created and
	 * passed the dispatchers ServiceContainer instance.
	 * @param  callable|string  $handler
	 * @return callable
	 */
	protected function makeHandler( $handler ) {

		// strings in Foo::bar format are classes that need to be instantiated with the service container
		if( $this->isControllerClassHandler($handler) ) {

			list($class, $method) = explode('::', $handler);

			// if $class begins with a namespace separator then assumes it's a fully qualified class name
			// otherwise first check services for registered controllers...
			if( substr($class, 0, 1) != '\\' ) {

				// do we have this controller registered in services?
				// (this is preferred way of doing it)
				if( isset($this->services[$class]) ) {
					$handler = [$this->services[$class], $method];

				} else {
					// assume it's in the current application or module's namespace
					$class = "{$this->namespace}\\controllers\\{$class}";
					$handler = [new $class($this->services), $method];
				}
			} else {
				// FQ classname
				$handler = [new $class($this->services), $method];
			}


		}

		// if our handler is an instance of the Controller interface then
		// wrap the handler in a closure that runs the __before and __after methods
		if( $this->isControllerInstanceHandler($handler) ) {

			list($controller, $method) = $handler;

			$handler = function() use ($controller, $method) {

				// extract the request from the passed function arguments
				$args     = func_get_args();
				$request  = reset($args);

				// run the before hook
				$response = $controller->__before($request);

				// if we didn't get a response from the before hook then
				// proceed with running the controller method and the after hook
				if( empty($response) ) {
					$response = $controller->__after(
						$request,
						call_user_func_array([$controller, $method], $args)
					);
				}

				return $response;

			};

		}

		return $handler;

	}

	/**
	 * Determines if the specified handler is a controller class/method handler
	 * in the format Foo::bar
	 * @param  mixed  $handler
	 * @return boolean
	 */
	protected function isControllerClassHandler( $handler ) {
		return is_string($handler) && strpos($handler, '::');
	}

	/**
	 * Determines if the specified handler is an instance of the Controller interface.
	 * @param  mixed  $handler
	 * @return boolean
	 */
	protected function isControllerInstanceHandler( $handler ) {
		return is_array($handler) && ($handler[0] instanceof Controller);
	}

}

// EOF
