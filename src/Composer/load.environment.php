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
// Joinup has been instructed by the devops team to use getenv() to import
// environment variables in settings.php, so we need to use the unsafe method.
// This means Joinup is not intended to be used in environments that use
// php-fpm. Also this file should not be included on any production environment
// for performance reasons.
// @see https://github.com/vlucas/phpdotenv/issues/446
$dotenv = Dotenv::createUnsafeImmutable($path, ['.env.dist', '.env'], FALSE);
$dotenv->safeLoad();
