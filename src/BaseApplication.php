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

/**
 * Application is basically a front controller class that acts as a "root object".
 */
class BaseApplication extends BaseDispatcher implements Application {

	/**
	 * Location of the applicatin on disk.
	 * @var string
	 */
	protected $path;

	/**
	 * Application's namespace.
	 * @var string
	 */
	protected $namespace;

	/**
	 * Dependency container object.
	 * @var \yolk\core\Services
	 */
	protected $services;

	/**
	 * Dependency container object.
	 * @param string $path        application's filesystem location
	 * @param string $namespace   the namespace used by the applications controllers, models, etc.
	 */
	public function __construct( $path, $namespace ) {
		$this->path      = $path;
		$this->namespace = $namespace;
	}

	/**
	 * Run the application.
	 * return \yolk\app\Response
	 */
	public function run() {
		return $this($this->services['request']);
	}

	public function __invoke( $request = null ) {
		
		try {

			$this->init();
			
			// if we we're passed a request then grab one
			if( !isset($request) )
				$request = $this->services['request'];

			$response = $this->dispatch($request);

			// convert strings to a 200 HTML response
			if( is_string($response) ) {
				$response = $this->stringToResponse($response);
			}
			// convert arrays to a 200 JSON response
			elseif( is_array($response) ) {
				$response = $this->arrayToResponse($response);
			}

			// TODO: this should be inverted by injecting the profiler into the response object in services.php
			$this->injectProfiler($response, $this->services['profiler']);

			return $response;

		}
		catch( \Exception $e ) {
			$this->error($e)->send();
		}

	}

	/**
	 * Load framework and application services and the application config file.
	 * Process autoload section of config file (if any).
	 * @return self
	 */
	public function init() {

		$this
			->loadServices()
			->loadConfig()
			->loadRoutes();

		return $this;

	}

	/**
	 * Register a listener for the 'app.beforeController' event.
	 * to continue or \yolk\app\Response to bypass routing with own response
	 * @param callback  $callback
	 * @returns self
	 */
	public function before( $callback ) {
		$this->services['event-manager']->addListener('app.beforeController', $callback);
		return $this;
	}

	/**
	 * Register a listener for the 'app.afterController' event.
	 * @param callback  $callback
	 * @returns self
	 */
	public function after( $callback ) {
		$this->services['event-manager']->addListener('app.afterController', $callback);
		return $this;
	}

	protected function error( $error, $context = array() ) {

		$config = $this->services['config'];

		// if this isn't a boring 404 then log it and if we're in debug mode then throw it to Yolk's exception handler
		if( !($error instanceof exceptions\NotFoundException) ) {
			error_log(get_class($error). ': '. $error->getMessage(). ' ['. $error->getFile(). ':'. $error->getLine(). ']');
			if( Yolk::isDebug() )
				throw $error;
		}

		$response = new \yolk\app\Response();

		// instances of \yolk\app\Exception have an HTTP status code as their error code
		if( $error instanceof Exception ) {
			$response->status($error->getCode(), $error->getMessage());
			$template = 'errors/'. $error->getCode();
		}
		// other errors should be 500s
		else {
			$response->status(500);
			$template = 'errors/500';
		}

		$view = $this->services["view.twig"];

		$context['error']       = $error;
		$context['config']      = $config->toArray();
		$context['profiler']    = isset($this->services['profiler']) ? $this->services['profiler']->getData() : array();
		$context['WEB_PATH']    = $config->get('paths.web');
		$context['STATIC_PATH'] = $config->get('paths.static');

		// do we have a template for this error?
		if( $view->exists($template) ) {
			$body = $view->render($template, $context);
		}
		// do we have a generic template?
		elseif( $view->exists('errors/generic') ) {
			$body = $view->render('errors/generic', $context);
		}
		// do we have a static error page?
		elseif( file_exists($config->get('paths.view'). '/error.html') ) {
			$body = file_get_contents($config->get('paths.view'). '/error.html');
		}
		// there's no application defined way to display the error so throw it to Yolk's exception handler
		else {
			throw $error;
		}

		return $this->injectProfiler(
			$response->body($body),
			$this->services['profiler']
		);

	}

	protected function injectProfiler( \yolk\app\Response $response, Profiler $profiler = null ) {

		if( !$profiler )
			return $response;

		$profiler->isRunning() && $profiler->stop();

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

	/**
	 * Loads the services used by the application.
	 * @param \yolk\core\Services  $services   an existing service container
	 * @param string               $file       a file containing service definitions
	 * @return $this
	 */
	protected function loadServices( $services = null, $file = '' )  {


		if( !$services )
			$services = new \yolk\core\Services();

		require __DIR__. '/../services.php';


		// default Yolk services
		//$services = Yolk::loadServices($services);

		// application-specific services
		$file && require $file;

		$this->services = $services;

		return $this;

	}

	/**
	 * Loads the application's configuration settings.
	 * @param string   $file   location of the config file
	 * @return $this
	 */
	protected function loadConfig( $file = '' )  {

		if( !$file )
			$file = "{$this->path}/config/main.php";

		$this->services['config']->load($file);

		return $this;

	}

	/**
	 * Loads the routes used by the application.
	 * @param string   $file   location of the routes file
	 * @return $this
	 */
	protected function loadRoutes( $file = '' ) {

		if( !$file )
			$file = "{$this->path}/routes.php";

		$router = $this->services['router'];

		require $file;

		return $this;

	}

}

// EOF