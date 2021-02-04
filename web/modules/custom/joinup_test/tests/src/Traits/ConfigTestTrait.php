<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_test\Traits;

use Drupal\Component\Serialization\Yaml;

/**
 * Config reusable testing helper methods.
 */
trait ConfigTestTrait {

  /**
   * Returns config sync configuration data.
   *
   * @param string $config_name
   *   The config to be retrieved from config sync directory.
   *
   * @return array
   *   Structured array with the configuration data.
   *
   * @throws \InvalidArgumentException
   *   If the passed configuration name is invalid.
   */
  protected function getConfigData(string $config_name): array {
    $config_sync_dir = realpath($this->root . '/../config/sync');
    $file_path = "{$config_sync_dir}/{$config_name}.yml";
    if (!file_exists($file_path) || !is_readable($file_path)) {
      throw new \InvalidArgumentException("File {$file_path} doesn't exist or is not readable.");
    }
    if (!$data = Yaml::decode(file_get_contents($file_path))) {
      throw new \InvalidArgumentException("File {$file_path} doesn't have a valid YAML syntax.");
    }
    return $data;
  }

  /**
   * Imports configs from config sync storage.
   *
   * @param string[] $config_names
   *   A list of config names to be imported.
   */
  protected function importConfigs(array $config_names): void {
    $config_factory = \Drupal::configFactory();
    foreach ($config_names as $config_name) {
      $config_data = $this->getConfigData($config_name);
      $config_factory->getEditable($config_name)->setData($config_data)->save();
    }
  }

}
