<?php

/**
 * @file
 * Includes custom settings for the UAT environment.
 */

$config['stage_file_proxy.settings']['origin'] = 'https://joinup.ec.europa.eu';
$config['stage_file_proxy.settings']['hotlink'] = TRUE;
$settings['config_readonly'] = !file_exists(getcwd() . '/../disable-config-readonly');
