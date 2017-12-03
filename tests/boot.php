<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the objects-hooks-remover package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if ( ! is_dir( dirname( __DIR__ ) . '/vendor/' ) ) {
	die( 'Please install via Composer before running tests.' );
}

error_reporting( E_ALL );

require_once __DIR__ . '/stubs.php';
require_once dirname( __DIR__ ) . '/vendor/autoload.php';
