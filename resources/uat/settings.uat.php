<?php

/**
 * @file
 * Includes custom settings for the UAT environment.
 */
$settings['redis.connection']['interface'] = 'Predis';
$settings['redis.connection']['host'] = 'joinup-uat-rds-01.y9zhag.0001.euw1.cache.amazonaws.com';
$settings['cache']['default'] = 'cache.backend.redis';
$settings['container_yamls'][] = DRUPAL_ROOT . 'modules/contrib/redis/example.services.yml';
