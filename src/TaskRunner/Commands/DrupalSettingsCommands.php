<?php

declare(strict_types = 1);

namespace Joinup\TaskRunner\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use OpenEuropa\TaskRunner\Commands\AbstractCommands;
use Robo\Collection\CollectionBuilder;
use Robo\Exception\AbortTasksException;
use Symfony\Component\Console\Input\InputOption;

/**
 * Provides commands to manipulate the Drupal settings.php file.
 */
class DrupalSettingsCommands extends AbstractCommands {

  /**
   * Builds the settings.php file from scratch.
   *
   * This command (re)creates a compact settings.php file by using the following
   * configurations defined under `drupal.settings`:
   * - drupal.settings.header: A block of code containing the `settings.php`
   *   header part. Example:
   *   @code
   *   drupal:
   *     settings:
   *       header: |
   *         <?php
   *         // @file settings.php
   *   @endcode
   * - drupal.settings.footer: A block of code containing the `settings.php`
   *   footer part. Example:
   *   @code
   *   drupal:
   *     settings:
   *       footer: |
   *         if (file_exists("$app_root/$site_path/settings.override.php")) {
   *           include "$app_root/$site_path/settings.override.php";
   *         }
   *   @endcode
   * - drupal.settings.sections: An associative array of settings sections. Each
   *   section is a block of code. The key should be descriptive as it's used as
   *   section title/comment. Example:
   *   @code
   *   drupal:
   *     settings:
   *       sections:
   *         Main settings: |
   *           $settings['hash_salt'] = getenv('DRUPAL_HASH_SALT');
   *           $settings['container_yamls'][] = "$app_root/$site_path/services.yml";
   *           ...
   *         Database: |
   *           $databases['default']['default'] = [
   *             'database' => getenv('DRUPAL_DATABASE_NAME'),
   *             ...
   *           ];
   *           ...
   *   @endcode
   * - drupal.settings.presets: An associative array, keyed by the preset ID.
   *   Each preset is an indexed array of sections. The sections will be placed
   *   following this order in the settings file. This helps build various
   *   structures of the settings file depending on the environment where the
   *   settings file is used. Example:
   *   @code
   *   drupal:
   *     settings:
   *       presets:
   *         base:
   *           - Main settings
   *           - Database
   *           - ...
   *         dev:
   *           - Main settings
   *           - Database
   *           - Development settings
   *           - ...
   *   @endcode
   *
   * @param string[] $presets
   *   A space separated list of preset IDs. A preset is an identifier
   *   representing a set of sections, defined in `drupal.settings.presets`
   *   configuration.
   * @param array $options
   *   The command options.
   *
   * @return \Robo\Collection\CollectionBuilder
   *   Collection builder.
   *
   * @command drupal:settings
   *
   * @option filename Settings file name.
   * @option root Drupal root.
   * @option sites-subdir Drupal site subdirectory.
   */
  public function settings(array $presets, array $options = [
    'filename' => 'settings.php',
    'root' => InputOption::VALUE_REQUIRED,
    'sites-subdir' => InputOption::VALUE_REQUIRED,
  ]): CollectionBuilder {
    $settingsFile = $this->getSettingsFilePath($options);
    $sections = $this->getSections((array) $presets);
    $availableSections = $this->getConfig()->get('drupal.settings.sections', []);

    $taskWriteToFile = $this->taskWriteToFile($settingsFile);

    if ($settingsHeader = $this->getConfig()->get('drupal.settings.header')) {
      $taskWriteToFile->line(trim($settingsHeader));
    }

    foreach ($sections as $section) {
      $taskWriteToFile
        ->lines(['', '', sprintf('// %s.', $section), ''])
        ->line(trim($availableSections[$section]));
    }

    if ($settingsFooter = $this->getConfig()->get('drupal.settings.footer')) {
      $taskWriteToFile->lines(['', '', trim($settingsFooter)]);
    }

    return $this->collectionBuilder()->addTask($taskWriteToFile);
  }

  /**
   * Validates the `drupal:settings` command.
   *
   * @param \Consolidation\AnnotatedCommand\CommandData $commandData
   *   The command data object.
   *
   * @throws \Robo\Exception\AbortTasksException
   *   If no preset has been passed or some passed presets are invalid or they
   *   contain invalid sections.
   *
   * @hook validate drupal:settings
   */
  public function validateSettings(CommandData $commandData): void {
    $presets = $commandData->arguments()['presets'];

    $availablePresets = array_keys($this->getConfig()->get('drupal.settings.presets', []));
    $invalidPresets = array_diff($presets, $availablePresets);
    if ($invalidPresets) {
      throw new AbortTasksException("Invalid presets: '" . implode("', '", $invalidPresets) . "'. Check the 'drupal.settings.presets' configuration.");
    }

    $sections = $this->getSections($presets);
    $availableSections = array_keys($this->getConfig()->get('drupal.settings.sections', []));
    $invalidSections = array_diff($sections, $availableSections);
    if ($invalidSections) {
      throw new AbortTasksException("Passed presets contain invalid sections: '" . implode("', '", $invalidSections) . "'. Check the 'drupal.settings' config.");
    }
  }

  /**
   * Returns the path to the settings file.
   *
   * @param array $options
   *   The command options.
   *
   * @return string
   *   The path to settings file.
   */
  protected function getSettingsFilePath(array $options): string {
    return $options['root'] . '/sites/' . $options['sites-subdir'] . '/' . $options['filename'];
  }

  /**
   * Returns a list of sections belonging to the passed parameters.
   *
   * @param array $presets
   *   A list of section presets.
   *
   * @return array
   *   A list of sections belonging to the passed parameters.
   */
  protected function getSections(array $presets): array {
    $allPresets = $this->getConfig()->get('drupal.settings.presets');
    $passedPresets = array_intersect_key($allPresets, array_flip($presets));
    if (count($passedPresets) < 2) {
      $passedPresets[] = [];
    }
    return array_values(array_unique(call_user_func_array('array_merge', $passedPresets)));
  }

}
