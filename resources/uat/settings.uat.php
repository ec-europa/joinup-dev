<?php

/**
 * @file
 * Includes custom settings for the UAT environment.
 */

// Stage file proxy.
$config['stage_file_proxy.settings']['origin'] = 'https://joinup.ec.europa.eu';
$config['stage_file_proxy.settings']['hotlink'] = TRUE;

// Newsletter service mock object.
$config['oe_newsroom_newsletter.subscriber']['class'] = 'Drupal\oe_newsroom_newsletter\NewsletterSubscriber\MockNewsletterSubscriber';

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

// Private file directory.
$settings['file_private_path'] = DRUPAL_ROOT . '/../private';

$settings['error_page']['uuid'] = TRUE;
$settings['error_page']['template_dir'] = DRUPAL_ROOT . '/../resources/error_page';
set_error_handler(['Drupal\error_page\ErrorPageErrorHandler', 'handleError']);
set_exception_handler(['Drupal\error_page\ErrorPageErrorHandler', 'handleException']);

$settings['config_sync_directory'] = DRUPAL_ROOT . '/../config/sync';
$settings['joinup']['sparql_public_endpoint'] = getenv('SPARQL_PUBLIC_ENDPOINT');
