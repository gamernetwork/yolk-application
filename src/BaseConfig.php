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

use yolk\contracts\app\Config;
use yolk\contracts\support\Arrayable;

/**
 * Simple PHP array-based, read-only configuration class.
 * 
 * Config files are simply PHP files that define arrays of key/value pairs.
 * $config = array(
 *    'logs' => array(
 *       'debug' => '/var/log/my_app/debug.log',
 *       'error' => '/var/log/my_app/error.log',
 *    ),
 *    'debug' => true
 * };
 *
 * Supports accessing nested keys using dot-notation:
 * * 'logs' will return all defined logs
 * * 'logs.debug' will return the debug log definition
 *
 */
class BaseConfig implements Config, Arrayable {
   
	/**
	 * Store for configuration values.
	 * @var array
	 */
	protected $data;

	public function __construct() {
		$this->data = [];
	}

	/**
	 * Loads and parses a configuration file.
	 * The file must define an array variable $config from which the data will be loaded.
	 * 
	 * @param string file   location of the configuration file.
	 * @throws \yolk\app\Exception if $config is not defined or not an array
	 */
	public function load( $file ) {

		require $file;

		if( !isset($config) || !is_array($config) )
			throw new Exception('Invalid Configuration');

		return $this->merge($config);

	}

	/**
	 * Determines if the specified key exists.
	 * 
	 * @param string key.
	 */
	public function has( $key ) {
		return $this->get($key) !== null;
	}

	/**
	 * Returns a specific item or branch of items.
	 * 
	 * @param string key       item to return.
	 * @param mixed  default   value returned if key doesn't exist in the configuration.
	 */
	public function get( $key, $default = null ) {
		
		$parts   = explode('.', $key);
		$context = &$this->data;

		foreach( $parts as $part ) {
			if( !isset($context[$part]) ) {
				return $default;
			}
			$context = &$context[$part];
		}

		return $context;

	}

	/**
	 * Assigns a new value to the specified key.
	 * 
	 * @param string key     item to set.
	 * @param mixed  value   new value.
	 */
	public function set( $key, $value ) {

		$parts   = explode('.', $key);
		$count   = count($parts) - 1;
		$context = &$this->data;

		for( $i = 0; $i <= $count; $i++ ) {
			$part = $parts[$i];
			if( !isset($context[$part]) && ($i < $count) ) {
				$context[$part] = [];
			}
			elseif( $i == $count ) {
				$context[$part] = $value;
				if( $parts[0] == 'php' ) {
					ini_set($part, $value);
				}
				return $this;
			}
			$context = &$context[$part];
		}

		return $this;

	}

	public function merge( array $config ) {

		foreach( $config as $k => $v ) {
			$this->set($k, $v);
		}

		return $this;

	}

	public function toArray() {
		return $this->data;
	}

}

// EOF