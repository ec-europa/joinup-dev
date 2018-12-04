<?php

/**
 * @file
 * Includes custom settings for the UAT environment.
 */

// Stage file proxy.
$config['stage_file_proxy.settings']['origin'] = 'https://joinup.ec.europa.eu';
$config['stage_file_proxy.settings']['hotlink'] = TRUE;

// Config read-only.
$settings['config_readonly'] = !file_exists(getcwd() . '/../disable-config-readonly');

// Redis.
$settings['redis.connection']['interface'] = 'Predis';
$settings['redis.connection']['host'] = 'joinup-uat-rds-01.y9zhag.0001.euw1.cache.amazonaws.com';
$settings['cache']['default'] = 'cache.backend.redis';
$settings['container_yamls'][] = DRUPAL_ROOT . 'modules/contrib/redis/example.services.yml';

// Configure swiftmailer spooling.
$config['swiftmailer.transport']['transport'] = 'spool';
$config['swiftmailer.transport']['spool_directory'] = '/tmp/spool/';
