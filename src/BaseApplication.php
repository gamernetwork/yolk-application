<?php
/*
 * This file is part of Yolk - Gamer Network's PHP Framework.
 *
 * Copyright (c) 2013 Gamer Network Ltd.
 * 
 * Distributed under the MIT License, a copy of which is available in the
 * LICENSE file that was bundled with this package, or online at:
 * https://github.com/gamernetwork/yolk-application
 */

namespace yolk\app;

use yolk\Yolk;

use yolk\contracts\app\Application;
use yolk\contracts\app\Dispatcher;
use yolk\contracts\app\Request;
use yolk\contracts\app\Response;
use yolk\contracts\profiler\Profiler;

use yolk\exceptions\Handler as YolkHandler;

/**
 * Application is basically a front controller class that acts as a "root object".
 */
abstract class BaseApplication extends BaseDispatcher implements Application {

	/**
	 * Location of the application in the filesystem.
	 * @var string
	 */
	protected $path;

	/**
	 * A service container instance.
	 * @var \yolk\app\ServiceContainer
	 */
	protected $services;

	/**
	 * An array of \yolk\app\BaseModule
	 * @var Array
	 */
	protected $modules;

	/**
	 * Dependency container object.
	 * @param string $path        application's filesystem location
	 */
	public function __construct( $path ) {
		parent::__construct();
		$this->path = $path;
		Yolk::setExceptionHandler([$this, 'error']);
		$this->modules = [];
	}

	/**
	 * Shortcut to run the application in a typical CGI context
	 *
	 * @return \yolk\app\Response
	 */
	public function run() {
		return $this(
			BaseRequest::createFromGlobals()
		);
	}

	/**
	 * Figure out what to do with a request
	 *
	 * @return \yolk\app\Response
	 */
	public function dispatch( Request $request ) {
		$response = parent::dispatch( $request );
		if( $response !== false ) {
			return $response;
		}
		throw new exceptions\NotFoundException();
	}

	/**
	 * This is our application error handler, called by Yolk::run().
	 * We'll send an appropriate HTTP status code and then display an error page.
	 * @param  \Exception $error
	 * @param  string     $error_page  the default error page to display
	 * @return void
	 */
	public function error( \Exception $error, $error_page ) {

		// default to a 500 error
		$code    = 500;
		$message = 'Internal Server Error';

		// if it's an application error then use the code and message provided
		if( $error instanceof Exception ) {
			$code    = $error->getCode();
			$message = $error->getMessage();
		}

		// send an appropriate header if we still can
		if( !headers_sent() )
			header("HTTP/1.1 {$code} {$message}");

		// do we have a specific error page?
		if( file_exists("{$this->path}/app/errors/{$code}.php") )
			$error_page = "{$this->path}/app/errors/{$code}.php";
		// or generic page
		elseif( file_exists("{$this->path}/app/errors/generic.php") )
			$error_page = "{$this->path}/app/errors/generic.php";

		// we don't log 404's - there might be lots of them!
		$log = !($error instanceof exceptions\NotFoundException);

		YolkHandler::exception($error, $error_page, $log);

	}

	/**
	 * Do any set up that needs an instantiated $this
	 */
	protected function init() {

		$this->loadServices();

		$this->loadConfig();

		$this->router = $this->services['router'];

		$this->loadModules();

		$this->registerControllers();

		$this->loadRoutes();

		// set up some middleware to handle flash messages,
		// do some profiling and set request prefix (if installed in folder)
		$this->addMiddleware(
			function( Request $request, callable $next = null ) {

				$request->setUriPrefix($this->services['config']->get('paths.web'));

				$response = $next($request);

				// if request contains flash messages and the response doesn't then we need to remove them
				if( $request->messages() && !$response->cookie('YOLK_MESSAGES') )
					$response->cookie('YOLK_MESSAGES', '', time() - 60);

				// TODO: this should be inverted by injecting the profiler into the response object in services.php
				$this->injectProfiler($response, $this->services['profiler']);

				return $response;
			}
		);

	}

	/**
	 * Sets up the service container and loads the services
	 * used by the application.
	 *
	 * @return void
	 */
	protected function loadServices() {

		$this->services = new ServiceContainer();

		// default Yolk service provider provides db connection manager, logging,
		// view factories, etc. You probably always do this unless you are
		// having some micro-framework fun.
		$this->services->register(
			new \yolk\ServiceProvider()
		);

	}

	/**
	 * Loads the application's configuration settings.
	 * @return void
	 */
	protected function loadConfig() {
		$this->services['config']->load("{$this->path}/config/main.php");
	}

	/**
	 * Loads the routes used by the application.
	 * e.g. $router->addRoute( '/articles/?$', '\\namespace\\controllers\\Controller::articles' );
	 * @return void
	 */
	abstract protected function loadRoutes();

	/**
	 * Here we set up a chain of modules which
	 * are simply mini child applications to which we can dispatch requests
	 * e.g. $this->modules['my-module'] = new \my\namespaced\Module( $this->services );
	 * @return void
	 */
	abstract protected function loadModules();

	/**
	 * Register any controller aliases I may need
	 */
	abstract protected function registerControllers();

	/**
	 * Alias a controller such that it can be called from the service by base name alone.
	 * This allows subclassed-Controllers to substitute in modules that assume base
	 * classes are used. It's a sort of dynamic OO design that obviates the need for
	 * empty stub controllers peppering the code.
	 * 
	 * @param  string $name The alias for this controller
	 * @param  string $fq_class Fully qualified namespaced class name
	 * @param  array  $opts Any further options used to initalise this controller
	 * @return void
	 */
	protected function registerController( $name, $fq_class, $opts = [] ) {
		$this->services[$name] = function( $c ) use ($fq_class, $opts) {
			return new $fq_class( $c, $opts );
		};
	}

	protected function injectProfiler( Response $response, Profiler $profiler = null ) {

		if( !$profiler )
			return $response;

		$profiler->isRunning() && $profiler->stop();

		$profiler->config($this->services['config']);

		$body = $response->body();

		$body = str_replace('%% YOLK_DURATION %%', number_format($profiler->getTotalElapsed() * 1000, 0), $body);
		$body = str_replace('%% YOLK_MEMORY %%', number_format(memory_get_peak_usage() / (1024 * 1024), 3), $body);
		$body = str_replace('%% YOLK_QUERIES %%', count($profiler->getQueries()), $body);
		$body = str_replace('%% YOLK_QUERY_TIME %%', number_format($profiler->getTotalElapsed('Query') * 1000, 0), $body);

		$response->body(
			str_replace(
				'%% YOLK_DEBUG %%',
				$profiler->getHTML(),
				$body
			)
		);

		return $response;
	}

}

// EOF
