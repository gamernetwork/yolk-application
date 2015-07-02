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

abstract class BaseDispatcher implements Dispatcher {

	/**
	 * A service container instance.
	 * @var \yolk\app\BaseServices
	 */
	protected $services;

	public function dispatch( Request $request ) {

		$config   = $this->services['config'];
		$response = $this->services['response'];
		$router   = $this->services['router'];

		// request contains flash messages so the response should remove them
		if( $request->messages() )
			$response->cookie('YOLK_MESSAGES', '', time() - 60);

		$request->setUriPrefix($config->get('paths.web'));

		$route = $router->match(
			$request->uri(),
			$request->method()
		);

		// pass through extra info about the route
		$request->extra($route['extra']);

		// make sure we have a callable handler
		$handler = $this->makeHandler($route['handler']);

		// prepend the request to the parameter array
		array_unshift($route['parameters'], $request);

		// execute the handler
		return call_user_func_array($handler, $route['parameters']);

	}

	protected function makeHandler( $handler ) {

		// strings in Foo::bar format are classes that need to be instantiated with the service container
		if( is_string($handler) && strpos($handler, '::') ) {

			list($class, $method) = explode('::', strpos($handler));
			
			// if $class begins with a namespace separator then assumes it's a fully qualified class name
			// otherwise prefix it with the application's namespace
			if( substr($class, 0, 1) != '\\' )
				$class = "{$this->namespace}\\controllers\\{$class}";

			$handler = [new $class($this->services), $method];

		}

		if( !is_callable($handler) )
			throw new \LogicException("Specified route handler is not callable");

	}

}

// EOF