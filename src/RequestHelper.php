<?php
/*
 * This file is part of Yolk - Gamer Network's PHP Framework.
 *
 * Copyright (c) 2014 Gamer Network Ltd.
 * 
 * Distributed under the MIT License, a copy of which is available in the
 * LICENSE file that was bundled with this package, or online at:
 * https://github.com/gamernetwork/yolk-application
 */

namespace yolk\app;

use yolk\contracts\app\Request as RequestInterface;

class RequestHelper {

	/**
	 * Extract the HTTP headers from the _SERVER superglobal.
	 *
	 * @param  array   $server   contents of the _SERVER superglobal
	 * @return array
	 */
	public static function getHeaders( $server ) {

		$headers = array();

		// iterate environment vars (possibly from CGI)
		foreach( $server as $k => $v ) {
			if( substr($k, 0, 5) == 'HTTP_' ) {
				$name = str_replace('_', ' ', strtolower(substr($k, 5)));
				$name = str_replace(' ', '-', $name);
				if( $name != 'cookie' )
					$headers[$name] = $v;
			}
		}

		if( isset($server['CONTENT_TYPE']) ) {
			$headers['content-type'] = $server['CONTENT_TYPE']; 
		}

		if( isset($server['CONTENT_LENGTH']) ) {
			$headers['content-length'] = $server['CONTENT_LENGTH']; 
		}

		return $headers;

	}

	/**
	 * Extract environment settings from the _SERVER superglobal.
	 *
	 * @param  array   $server   contents of the _SERVER superglobal
	 * @return array
	 */
	public static function getEnvironment( $server ) {

		$environment = array();

		foreach( $server as $k => $v ) {
			if( substr($k, 0, 5) != 'HTTP_' && !in_array($k, array('CONTENT_TYPE', 'CONTENT_LENGTH', 'REQUEST_METHOD')) ) {
				$environment[$k] = $v;
			}
		}

		return $environment;

	}

	/**
	 * Determine the request data based on the method.
	 * Reads from the input stream for PUT requests.
	 *
	 * @param  string  $method   request method
	 * @param  array   $post     contents of the _POST superglobal
	 * @return array
	 */
	public static function getData( $method, array $post ) {

		$data = array();

		if( $method == RequestInterface::METHOD_PUT )
			parse_str(file_get_contents('php://input'), $data);

		elseif( $method == RequestInterface::METHOD_POST )
			$data = $post;

		return $data;

	}

	/**
	 * Extract valid file uploads from the _FILES superglobal.
	 *
	 * @param  array   $files   contents of the _FILES superglobal
	 * @return array
	 */
	public static function getFiles( $files ) {
		$cleaned = array();
		foreach( $files as $k => $f ) {
			if( !$f['error'] )
				$cleaned[$k] = $f;
		}
		return $cleaned;
	}


}

// EOF