<?php

/**
 * @file
 * Loads environment specific configuration.
 *
 * This file is included very early. See autoload.files in composer.json.
 * @see https://getcomposer.org/doc/04-schema.md#files
 */

declare(strict_types = 1);

use Dotenv\Dotenv;

// Load the .env.dist file in the project root, and overridden settings in .env
// if it exists.
$path = dirname(__DIR__, 2);
$dotenv = Dotenv::createImmutable($path, ['.env.dist', '.env'], FALSE);
$dotenv->safeLoad();
