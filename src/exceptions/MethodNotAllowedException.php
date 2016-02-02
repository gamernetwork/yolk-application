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

namespace yolk\app\exceptions;

/**
 * 405 Method Not Allowed Exception
 */
class MethodNotAllowedException extends \yolk\app\Exception {

	protected $allowed;

	public function __construct( array $allowed = [], $message = 'Method Not Allowed', \Exception $previous = null ) {
		parent::__construct($message, 405, $previous);
		$this->allowed = $allowed;
	}

	/**
	 * Return an array of methods that are allowed.
	 * @return array
	 */
	public function getAllowed() {
		return $this->allowed;
	}

}

// EOF