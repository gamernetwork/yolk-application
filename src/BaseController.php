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

use yolk\contracts\app\Controller;
use yolk\contracts\app\Request as NewRequest;
use yolk\contracts\app\Response as NewResponse;

use yolk\view\View;

/**
 * Base controller object.
 */
abstract class BaseController implements Controller {

	/**
	 * Dependency container and factory.
	 * @var \yolk\app\ServiceContainer
	 */
	protected $services;

	protected $view_path;

	/**
	 * Make sure we store the dependency container.
	 * @param ServiceContainer $services    services available to the controller.
	 */
	public function __construct( \yolk\app\ServiceContainer $services, $opts = [] ) {
		$this->services = $services;

		// we can set a view_path override per controller
		if( isset( $opts['view_path'] ) ) {
			$this->view_path = $opts['view_path'];
		}
	}

	public function __before( NewRequest $request ) {
		return false;
	}

	public function __after( NewRequest $request, NewResponse $response ) {
		return $response;
	}

	/**
	 * Return a response using the specified body.
	 *
	 * @param string   $body
	 * @return \yolk\app\Response
	 */
	protected function respond( $body ) {
		// get a nice fresh response from the factory
		$response = $this->services['response'];

		// set response body (TODO: enough with the verbless methods)
		$response->body($body);
		return $response;
	}

	/**
	 * Render the specified template into the body of a response object and return the response.
	 *
	 * @param string                 $template the name of the view template to use
	 * @param array                  $context  data for the view
	 * @param string|\yolk\view\View $view     the view adapter to use
	 * @return \yolk\app\Response
	 */
	protected function respondView( $template, $context = array(), $view = 'twig' ) {

		$context['config']      = $this->services['config']->toArray();
		$context['profiler']    = isset($this->services['profiler']) ? $this->services['profiler']->getHTML() : array();

		$context['WEB_PATH']    = $this->services['config']->get('paths.web');
		$context['STATIC_PATH'] = $this->services['config']->get('paths.static');

		// if we weren't given a view instance then we should create one using the specified configuration
		if( !($view instanceof View) ) {

			// get view config defaults
			$config = $this->services['config']->get("views.{$view}");

			// override view path with this controllers path settings (provided at construct time)
			$config['view_path'] = $this->view_path;

			$vobj = $this->services["view"]->create($config);

			// used extensions are defined in the config file under the extensions option.
			// we then use the view name/type as a prefix and ask the service container to
			// provide an instance of the extension that we can inject...
			foreach( \yolk\Yolk::get($config, 'extensions', []) as $extension ) {
				$vobj->addExtension($this->services["{$view}.{$extension}"]);
			}

			// context manager contains stuff we want in globally available view context
			$context_manager = $this->services['context_manager'];
			foreach( $context_manager as $k => $v ) {
				$vobj->assign($k, $v);
			}
		}

		return $this->respond(
			$vobj->render($template, $context)
		);

	}

	/**
	 * Render the specified data as a json string into the body of a response object and return the response.
	 *
	 * @param mixed    $data   the data to encode
	 * @return \yolk\app\Response
	 */
	protected function respondJSON( $data ) {
		// TODO use a serialiser than can cope a bit better
		return $this->respond(json_encode($data))->header("Content-Type", "application/json");
	}

	/**
	 * Shortcut method to create a redirect response.
	 *
	 * @param string   $url
	 * @param bool     $permanent
	 * @param bool     $prefix    should the url be prefixed?
	 * @return \yolk\app\Response
	 */
	protected function redirect( $url, $permanent = false, $prefix = null ) {
		return $this->respond('')->redirect($url, $permanent, $prefix);
	}

}

// EOF
