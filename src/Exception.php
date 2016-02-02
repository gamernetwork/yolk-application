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

/**
 * Application exceptions should correspond to appropriate HTTP status codes.
 * This is the default catch-all exception that generates an 500 response code.
 * Applications should throw a more specific exception whenever possible.
 */
class Exception extends \Exception {

	public function __construct( $message = 'Internal Server Error', $code = 500, \Exception $previous = null ) {
		parent::__construct($message, $code, $previous);
	}

	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message}";
	}

}

// EOF