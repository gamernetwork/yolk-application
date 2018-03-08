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
die();

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
	 * Dependency container object.
	 * @param string $path        application's filesystem location
	 */
	public function __construct( $path ) {

		try {

			parent::__construct();

			$this->path = $path;

			$this->loadServices();
			$this->loadConfig();
			$this->loadRoutes();

		}
		catch( \Exception $e ) {
			$this->error($e);
		}

	}

	/**
	 * Run the application.
	 * return \yolk\app\Response
	 */
	public function run() {
		return $this();
	}

	public function __invoke( Request $request = null ) {

		try {

			$response = null;

			// no request was specified so create one from the PHP super-globals
			if( !$request )
				$request = BaseRequest::createFromGlobals();

			$response = $this->dispatch($request);

			// if request contains flash messages and the response doesn't then we need to remove them
			if( $request->messages() && !$response->cookie('YOLK_MESSAGES') )
				$response->cookie('YOLK_MESSAGES', '', time() - 60);

			// TODO: this should be inverted by injecting the profiler into the response object in services.php
			$this->injectProfiler($response, $this->services['profiler']);

			$response->send();

			return $response;

		}
		catch( \Exception $e ) {
			$context = [
				'request'  => $request,
				'response' => $response,
			];
			$this->error($e, $context);
		}

	}

	public function dispatch( Request $request ) {

		$request->setUriPrefix($this->services['config']->get('paths.web'));

		return parent::dispatch($request, $this->services);

	}

	protected function error( \Exception $error, $context = [] ) {

		// if this isn't a 404 then log it and if we're in debug mode then throw it to Yolk's exception handler
		if( !($error instanceof exceptions\NotFoundException) ) {
			if( Yolk::isDebug() )
				throw $error;
			error_log(get_class($error). ': '. $error->getMessage(). ' ['. $error->getFile(). ':'. $error->getLine(). ']');
		}

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
			include "{$this->path}/app/errors/{$code}.php";
		// otherwise
		elseif( file_exists("{$this->path}/app/errors/generic.php") )
			include "{$this->path}/app/errors/generic.php";
		else
			throw $error;

	}

	protected function injectProfiler( Response $response, Profiler $profiler = null ) {

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
	 * @return void
	 */
	protected function loadServices()  {

		$container = new ServiceContainer();

		require "{$this->path}/app/services.php";

		$this->services = $container;

	}

	/**
	 * Loads the application's configuration settings.
	 * @return void
	 */
	protected function loadConfig()  {

		$this->services['config']->load("{$this->path}/config/main.php");

	}

	/**
	 * Loads the routes used by the application.
	 * @return void
	 */
	protected function loadRoutes() {

		$router = $this->services['router'];

		require "{$this->path}/app/routes.php";

		$this->router = $router;

	}

}

// EOF
