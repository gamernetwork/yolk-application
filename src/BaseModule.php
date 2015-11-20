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
class BaseModule extends BaseDispatcher implements Module {

	/**
	 * Location of the module class in the filesystem.
	 * @var string
	 */
	protected $path;

	/**
	 * A service container instance.
	 * @var \yolk\app\ServiceContainer
	 */
	protected $services;

	public function __construct( ServiceContainer $services ) {

		$class = new \ReflectionClass($this);
		$this->path      = pathinfo($class->getFileName(), PATHINFO_DIRNAME);
		$this->namespace = $class->getNamespaceName();

		$this->services = $services;

		$this->loadRoutes();

	}

	protected function init() {
		
	}

	/**
	 * Loads the routes used by the module.
	 * By default routes are contained in the routes.php file located in the same
	 * @return void
	 */
	protected function loadRoutes() {

		$router = $this->services['router'];

		require "{$this->path}/routes.php";

		$this->router = $router;

	}

}

// EOF