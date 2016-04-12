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

use yolk\Yolk;

use yolk\contracts\app\Request;

/**
 * A request object to wrap PHP super globals into a consistent interface.
 */
class BaseRequest implements Request {

	/**
	 * HTTP method used to make the request.
	 * @var string
	 */
	protected $method;

	/**
	 * URI of the request.
	 * @var string
	 */
	protected $uri;

	/**
	 * Query string of the request.
	 * @var string
	 */
	protected $query_string;

	/**
	 * Portion of the uri to ignore.
	 * @var array
	 */
	protected $uri_prefix;

	/**
	 * key/value array of request options (URL parameters).
	 * @var array
	 */
	protected $options;

	/**
	 * key/value array of request data (POST/PUT data).
	 * @var array
	 */
	protected $data;

	/**
	 * Array of HTTP headers sent.
	 * @var array
	 */
	protected $headers;

	/**
	 * Array of HTTP cookies sent.
	 * @var array
	 */
	protected $cookies;

	/**
	 * Array of files uploaded.
	 * @var array
	 */
	protected $files;

	/**
	 * Array of messages passed via the YOLK_MESSAGES cookie.
	 * @var array
	 */
	protected $messages;

	/**
	 * Array of environment variables.
	 * @var array
	 */
	protected $environment;

	/**
	 * IP address of client.
	 * @var string
	 */
	protected $ip;

	/**
	 * ISO 3166-1-alpha-2 country code of client IP address.
	 * This is populated if the geoip extension is enabled.
	 * @var string
	 */
	protected $country_code;

	/**
	 * Two letter country_code representing the continent the client IP address is based on.
	 * @var string
	 */
	protected $continent;

	/**
	 * Any extra data passed from router to request that can't be calculated from URL
	 * @var array
	 */
	protected $extra;

	public static function createFromArgs($args) {

		$args += [
			'method'		=> 'GET',
			'uri'			=> '/',
			'options'		=> [],
			'data'			=> [],
			'cookies'		=> [],
			'files'			=> [],
			'headers'		=> [],
			'environment'	=> []
		];

		return new static(
			$args['method'],
			$args['uri'],
			$args['options'],
			$args['data'],
			$args['cookies'],
			$args['files'],
			$args['headers'],
			$args['environment']
		);
	}

	public static function createFromGlobals() {
		return new static(
			$_SERVER['REQUEST_METHOD'],
			$_SERVER['REQUEST_URI'],
			$_GET,
			RequestHelper::getData($_SERVER['REQUEST_METHOD'], $_POST),
			$_COOKIE,
			RequestHelper::getFiles($_FILES),
			RequestHelper::getHeaders($_SERVER),
			RequestHelper::getEnvironment($_SERVER)
		);
	}

	/**
	 * Construct a new request from the specified data.
	 * @param string method        Usually $_GET superglobal.
	 * @param string uri           Usually $_GET superglobal.
	 * @param array  options       Usually $_GET superglobal.
	 * @param array  data          Usually $_POST superglobal.
	 * @param array  cookies       Usually $_COOKIES superglobal.
	 * @param array  files         Usually $_FILES superglobal.
	 * @param array  headers       Usually $_SERVER superglobal.
	 * @param array  environment   Usually $_SERVER superglobal.
	 */
	public function __construct( $method, $uri, array $options, array $data, array $cookies, array $files, array $headers, array $environment ) {

		$this->method = strtoupper($method);

		if( isset($headers['x-http-method-override']) && $headers['x-http-method-override'] ) {
			$this->method = $headers['x-http-method-override'];
		}

		// split uri into url and query string parts
		list($this->uri, $this->query_string) = explode('?', urldecode($uri)) + array('', '');
		$this->uri = rtrim($this->uri, '/');
		if( $this->query_string )
			$this->query_string = "?{$this->query_string}";

		$this->messages = array();
		if( isset($cookies['YOLK_MESSAGES']) ) {
			$this->messages = unserialize(base64_decode($cookies['YOLK_MESSAGES']));
			unset($cookies['YOLK_MESSAGES']);
		}

		$this->options      = $options;
		$this->data         = $data;
		$this->cookies      = $cookies;
		$this->files        = $files;
		$this->headers      = $headers;
		$this->environment  = $environment;
		$this->ip           = $this->getIP();
		$this->country_code = '';
		$this->continent    = '';
		$this->extra        = [];
		$this->uri_prefix   = [];

		// do geo-ip stuff if we have a valid IP that isn't part of a private network
		if( $this->ip ) {
			$this->country_code = function_exists('geoip_country_code_by_name')   ? geoip_country_code_by_name($this->ip)   : '';
			$this->continent    = function_exists('geoip_continent_code_by_name') ? geoip_continent_code_by_name($this->ip) : '';
		}

	}

	public function pushUriPrefix( $prefix ) {
		$this->uri_prefix[] = $prefix;
	}

	public function popUriPrefix() {
		return array_pop($this->uri_prefix);
	}

	/**
	 * Override the internal URI prefix stack
	 */
	public function setUriPrefix( $prefix ) {

		$this->uri_prefix = [];

		if( is_array($prefix) )
			$this->uri_prefix = $prefix;
		elseif( $prefix )
			$this->uri_prefix = [$prefix];

		return $this;

	}

	/**
	 * Returns the request method.
	 * @return string
	 */
	public function method() {
		return $this->method;
	}

	/**
	 * Returns the request URI minus any set prefix.
	 * @return string
	 */
	public function uri() {

	 	// Turn URI prefix stack into a string by concatenating the stack
	 	// elements and removing multiple/extraneous forward slashes
		$uri_prefix = preg_replace('#/{2,}#', '/', implode('', $this->uri_prefix));

		if( $this->uri == $uri_prefix )
			return '/';
		else
			return preg_replace('/^'. preg_quote($uri_prefix, '/'). '/', '', $this->uri);

	}

	/**
	 * Returns the full request URI.
	 * @return string
	 */
	public function fullURI() {
		return $this->uri;
	}

	public function queryString() {
		return $this->query_string;
	}

	/**
	 * Returns the value of a request option.
	 * @param  string $key       key to return value of; if empty all options are returned.
	 * @param  string $default   default value returned if key doesn't exist.
	 * @param  string $clean     is the value passed through the XSS filter.
	 * @return string|array
	 */
	public function option( $key = null, $default = null, $clean = true ) {
		$value = Yolk::get($this->options, $key, $default);
		return $clean ? Yolk::xssClean($value) : $value;
	}

	/**
	 * Returns an item of data from the request.
	 * @param  string $key       key to return value of; if empty all data items are returned.
	 * @param  string $default   default value returned if key doesn't exist.
	 * @param  string $clean     is the value passed through the XSS filter.
	 * @return string|array
	 */
	public function data( $key = null, $default = null, $clean = true ) {
		$value = Yolk::get($this->data, $key, $default);
		return $clean ? Yolk::xssClean($value) : $value;
	}

	/**
	 * Returns the value of a request header.
	 * @param  string $name      name of the header to return value of.
	 * @param  string $default   default value returned if header doesn't exist.
	 * @return string
	 */
	public function header( $name = null, $default = null ) {
		// normalise header name
		$name = strtolower(preg_replace('/[ _]+/', '-', $name));
		return Yolk::get($this->headers, $name, $default);
	}

	/**
	 * Returns the value of a request cookie.
	 * @param  string $name      name of the cookie to return value of.
	 * @param  string $default   default value returned if cookie doesn't exist.
	 * @return string
	 */
	public function cookie( $name = null, $default = null ) {
		return Yolk::get($this->cookies, $name, $default);
	}

	/**
	 * Returns a file upload item.
	 * @param  string $key       key to return item of.
	 * @return array
	 */
	public function file( $key = null, $default = null ) {
		return Yolk::get($this->files, $key, $default);
	}

	/**
	 * Returns the value of a environment setting.
	 * @param  string $name      name of the cookie to return value of.
	 * @param  string $default   default value returned if setting doesn't exist.
	 * @return string
	 */
	public function environment( $name = null, $default = null ) {
		return Yolk::get($this->environment, $name, $default);
	}

	public function extra( $key = null, $default = null ) {
		if( is_array($key) ) {
			$this->extra = $key;
			return $this;
		}
		else {
			return Yolk::get($this->extra, $key, $default);
		}
	}

	public function messages() {
		return $this->messages;
	}

	public function ip() {
		return $this->ip;
	}

	public function authUser() {

		// authenticated user location can vary between php/apache/nginx/php-fpm combo's and configs
		// so just try several possible keys and return the first non-empty one
		foreach( ['PHP_AUTH_USER', 'AUTHENTICATE_USERNAME', 'REMOTE_USER'] as $k ) {
			if( !empty($this->environment[$k]) )
				return $this->environment[$k];
		}

		return '';

	}

	public function authPassword() {
		return isset($this->environment['PHP_AUTH_PW']) ? $this->environment['PHP_AUTH_PW'] : '';
	}

	public function country( $default = 'GB' ) {
		return $this->country_code ? $this->country_code : $default;
	}

	public function continent( $default = 'EU' ) {
		return $this->continent ? $this->continent : $default;
	}

	public function isBot() {
		$ua = $this->header('User-Agent');
		return $ua ? preg_match("/bot|crawl|slurp|spider|archive/", $ua) > 0 : false;
	}

	public function isAjax() {
		return strtolower($this->header('x-requested-with')) == 'xmlhttprequest';
	}

	public function isSecure() {
		$https = $this->environment('HTTPS');
		return !empty($https) && ($https !== 'off'); ;
	}

	public function isGet() {
		return $this->method == self::METHOD_GET;
	}

	public function isPost() {
		return $this->method == self::METHOD_POST;
	}

	public function isPut() {
		return $this->method == self::METHOD_PUT;
	}

	public function isDelete() {
		return $this->method == self::METHOD_DELETE;
	}

	protected function getIP() {

		// TODO: make this better - very specific to our setup, i.e. varnish

		$ip        = $this->environment('REMOTE_ADDR');
		$forwarded = $this->header('X-Forwarded-For');

		if( $ip == '127.0.0.1' && $forwarded ) {
			$ips = explode(',', $forwarded);
			$ip = array_shift($ips);
		}

		// make sure we have a valid IP address that isn't part of a private network or reserved range
		return (string) filter_var($ip, FILTER_VALIDATE_IP, array('flags' => FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE));

	}

}

// EOF