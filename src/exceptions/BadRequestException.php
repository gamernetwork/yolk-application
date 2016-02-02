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
 * 400 Bad Request Exception
 */
class BadRequestException extends \yolk\app\Exception {

	public function __construct( $message = 'Bad Request', \Exception $previous = null ) {
		parent::__construct($message, 400, $previous);
	}

}

// EOF