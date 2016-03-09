<?php
/*
 * This file is part of Yolk - Gamer Network's PHP Framework.
 *
 * Copyright (c) 2013 Gamer Network Ltd.
 * 
 * Distributed under the MIT License, a copy of which is available in the
 * LICENSE file that was bundled with this package, or online at:
 * https://github.com/gamernetwork/yolk
 */

namespace yolk\app;

use yolk\Yolk;

use yolk\contracts\support\Config;

use yolk\database\DSN;

/**
 * Dependency injection container and factory for Yolk.
 * Resolves framework services and allows them to be swapped out as required.
 */
class ServiceContainer extends \Pimple\Container {

	/**
	 * Gets a parameter or an object.
	 * log.<name>, db.<name> and cache.<name> are acceptable shortcuts for creating services based on configuration entries.
	 * @param  string $id The unique identifier for the parameter or object
	 * @return mixed  The value of the parameter or an object
	 * @throws InvalidArgumentException if the identifier is not defined
	 */
	public function offsetGet( $id ) {

		// key doesn't exist but it might be a shortcut
		if( !parent::offsetExists($id) && ($shortcut = $this->matchShortcut($id)) ) {
			list($type, $name) = $shortcut;
			if( $config = $this->getConfig() )
				parent::offsetSet($id, $this->$type($name, $config));
		}

		return parent::offsetGet($id);

	}


	public function offsetExists( $id ) {

		if( $exists = parent::offsetExists($id) )
			return $exists;

		// if $id is a shortcut and we have a valid config instance then check if there is a valid config for the shortcut
		if( ($shortcut = $this->matchShortcut($id)) && ($config = $this->getConfig()) ) {

			list($type, $name) = $shortcut;

			$sections = [
				'cache' => 'caches',
				'db'    => 'databases',
				'log'   => 'logs',
				'view'  => 'views',
			];

			if( isset($sections[$type]) ) {
				$exists = $config->has(sprintf("%s.%s", $sections[$type], $name));
			}

		}

		return $exists;

	}

	protected function matchShortcut( $id ) {
		if( preg_match('/^(cache|db|log|view)\.(.*)$/', $id, $m) ) {
			return [
				$m[1],
				$m[2],
			];
		}
		return [];
	}

	protected function getConfig() {

		$config = false;

		if( parent::offsetExists('config') )
			$config = parent::offsetGet('config');

		if( !$config instanceof Config )
			$config = false;

		return $config;

	}

	protected function cache( $name, Config $config ) {

		if( !$config = $config->get("caches.{$name}") )
			throw new \LogicException("No configuration for cache '{$name}'");

		$cache = parent::offsetGet('cache')->create($config);

		return $cache;

	}

	protected function db( $name, Config $config ) {

		if( !$config = $config->get("databases.{$name}") )
			throw new \LogicException("No configuration for database '{$name}'");

		if( is_array($config) )
			$dsn = new DSN($config);
		else
			$dsn = DSN::fromString($config);

		$db = parent::offsetGet('db')->add($name, $dsn);

		parent::offsetExists('profiler') && $db->setProfiler(parent::offsetGet('profiler'));

		return $db;

	}

	protected function log( $name, Config $config ) {
		
		if( !$config = $config->get("logs.{$name}") )
			throw new \LogicException("No configuration for log '{$name}'");

		return parent::offsetGet('log')->create($config);
	}

}

// EOF
