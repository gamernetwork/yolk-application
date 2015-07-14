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

use yolk\view\View;

/**
 * Base controller object.
 */
abstract class BaseController {

	/**
	 * Dependency container and factory.
	 * @var \yolk\app\ServiceContainer
	 */
	protected $services;

	/**
	 * Make sure we store the dependency container.
	 * @param ServiceContainer $services    services available to the controller.
	 */
	public function __construct( \yolk\app\ServiceContainer $services ) {
		$this->services = $services;
	}

	/**
	 * Return a response using the specified body.
	 *
	 * @param string   $body
	 * @return \yolk\app\Response
	 */
	protected function respond( $body ) {
		$response = $this->services['response'];
		return $response->body($body);
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
		if( !($view instanceof View) )
			$view = $this->services["view.{$view}"];

		return $this->respond(
			$view->render($template, $context)
		);

	}

	/**
	 * Render the specified data as a json string into the body of a response object and return the response.
	 *
	 * @param mixed    $data   the data to encode
	 * @return \yolk\app\Response
	 */
	protected function respondJSON( $data ) {
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