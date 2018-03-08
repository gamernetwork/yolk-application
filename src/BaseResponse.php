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

use yolk\contracts\app\Response;

class BaseResponse implements Response {

	/**
	 * Default HTTP status messages.
	 * @var array
	 */
	protected static $statuses = [
		// info 1xx
		100 => 'Continue',
		101 => 'Switching Protocols',

		// success 2xx
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',

		// redirection 3xx
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',

		// client error 4xx
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',

		// server error 5xx
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		509 => 'Bandwidth Limit Exceeded'
	];

	/**
	 * HTTP status code and message.
	 * @var array
	 */
	protected $status;

	/**
	 * HTTP headers.
	 * @var array
	 */
	protected $headers;

	/**
	 * Cookie array.
	 * @var array
	 */
	protected $cookies;

	/**
	 * The main body of the response.
	 * @var string
	 */
	protected $body;

	/**
	 * The charset of the response
	 * @var string
	 */
	protected $charset;

	/**
	 * An array of messages to be sent with the response.
	 * @var array
	 */
	protected $messages;

	/**
	 * Path to prepend to redirects.
	 * This should usually be set to the value of paths.web from the application config.
	 * @var string
	 */
	protected $redirect_prefix;

	public function __construct() {

		$this->charset = "UTF-8";

		$this->status(200)
			 ->body('');

		$this->headers  = [
			'Content-Type' => "text/html; charset={$this->charset}",
		];
		$this->cookies  = [];
		$this->messages = [];

	}

	public function getCharset() {
		return $this->charset;
	}

	public function setCharset( $charset ) {
		$this->charset = strtoupper($charset);
		if( isset($this->headers['Content-Type']) ) {
			$this->headers['Content-Type'] = preg_replace("/charset=([a-z0-9\-]*)/i", "charset={$this->charset}", $this->headers['Content-Type']);
		}
		return $this;
	}

	public function isRedirect() {
		return in_array($this->status['code'], [301, 302, 303, 307, 308]);
	}

	public function status( $code = null, $message = '' ) {

		if( !$code )
			return $this->status;

		if( !isset(static::$statuses[$code]) )
			throw new Exception("{$code} is not a valid HTTP status code");

		$this->status = [
			'code'    => $code,
			'message' => $message ? $message : static::$statuses[$code]
		];

		return $this;

	}

	public function header( $name = null, $value = null ) {

		// no header name so return all headers
		if( !$name )
			return $this->headers;

		// normalise header names
		$name = str_replace( '- ', '-', ucwords( str_replace( '-', '- ', $name ) ) );

		// no value specified so return current value
		if( $value === null )
			return isset($this->headers[$name]) ? $this->headers[$name] : null;

		if( ($name == "Content-Type") && !strpos($value, 'charset') )
			$value .= "; charset={$this->charset}";

		$this->headers[$name] = $value;

		return $this;

	}

	public function body( $body = null ) {

		if( $body === null )
			return $this->body;

		$this->body = $body;

		return $this;

	}

	public function cookie( $name = null, $value = null, $expires = 0, $path = '/', $domain = '' ) {

		// no cookie name specified so return all cookies
		if( !$name )
			return $this->cookies;

		// no value specified so return current cookie
		if( $value === null )
			return isset($this->cookies[$name]) ? $this->cookies[$name] : [];

		$this->cookies[$name] =  [
			'value'   => $value,
			'expires' => $expires ? time() + $expires : 0,
			'path'    => $path,
			'domain'  => $domain,
		];

		return $this;

	}

	public function message( $text, $type = self::MSG_INFO, $title = '' ) {
		$this->messages[] = [
			'type'  => $type,
			'title' => $title,
			'text'  => $text,
		];
		return $this;
	}

	public function send() {

		header("HTTP/1.1 {$this->status['code']} {$this->status['message']}");

		foreach ($this->headers as $name => $value) {
			header("{$name}: $value");
		}

		// messages cookie
		if( isset($this->messages) && !empty($this->messages) ) {
			setcookie('YOLK_MESSAGES', base64_encode(serialize($this->messages)), 0, '/');
			unset($this->cookies['YOLK_MESSAGES']);
		}
		else {
			$existing = array_key_exists('YOLK_MESSAGES', $_COOKIE) 
				? $_COOKIE['YOLK_MESSAGES'] : null;
			// if the YOLK_MESSAGES cookie is a serialized empty array, we should unset it
			$existing_cookie_empty = $existing == "YTowOnt9";
			if( $existing_cookie_empty ) {
				$this->cookie('YOLK_MESSAGES', '', time() - 60);
			}
		}

		foreach( $this->cookies as $name => $cookie ) {
			setcookie($name, $cookie['value'], $cookie['expires'], $cookie['path'], $cookie['domain']);
		}

		echo $this->body;

		return $this;

	}

	public function setRedirectPrefix( $prefix ) {
		$this->redirect_prefix = $prefix;
		return $this;
	}

	public function redirect( $url, $permanent = false, $prefix = null ) {
		
		if( $this->redirect_prefix ) {
			// caller couldn't be bothered to specify so decide for them
			if( $prefix === null )
				$prefix = !preg_match('/^https?:\/\//', $url) && !preg_match('/^'.preg_quote($this->redirect_prefix, '/').'/', $url);
			if( $prefix )
				$url = $this->redirect_prefix. $url;
		}

		$this->status( $permanent ? 301 : 302)
			 ->header('Location', $url);

		return $this;

	}

}

// EOF
