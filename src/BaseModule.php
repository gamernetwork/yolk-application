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

use yolk\contracts\app\Module;
use yolk\contracts\app\Request;
use yolk\contracts\app\Response;

/**
 * Modules are mini-applications, they receive requests from an application and
 * dispatch them to controllers within the module. The responses are then
 * returned to the application.
 * Modules contain their own router instance, independant of the application.
 */
abstract class BaseModule extends BaseDispatcher implements Module {

	/**
	 * Location of the module class in the filesystem.
	 * @var string
	 */
	protected $path;

	public function __construct( ServiceContainer $services ) {

		$this->services = $services;

		$this->router = $services['router'];

		$class = new \ReflectionClass($this);
		$this->path      = pathinfo($class->getFileName(), PATHINFO_DIRNAME);
		$this->namespace = $class->getNamespaceName();

		$this->middleware = [];

		$this->loadRoutes();

	}

	/**
	 * Define the routes used by the module.
	 * @return void
	 */
	abstract protected function loadRoutes();

}

// EOF
