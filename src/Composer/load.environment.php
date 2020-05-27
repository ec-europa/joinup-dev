<?php

/**
 * This file is included very early. See autoload.files in composer.json and
 * https://getcomposer.org/doc/04-schema.md#files
 */

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;

// Load the .env.dist file in the project root, and overridden settings in .env
// if it exists.
$path = dirname(__DIR__, 2);
$dotenv = Dotenv::createImmutable($path, ['.env.dist', '.env'], false);
$dotenv->safeLoad();

var_dump(getenv());
