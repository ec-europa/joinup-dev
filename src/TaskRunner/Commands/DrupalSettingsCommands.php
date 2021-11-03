<?php

declare(strict_types = 1);

namespace Joinup\TaskRunner\Commands;

use OpenEuropa\TaskRunner\Commands\AbstractCommands;
use Robo\Collection\CollectionBuilder;
use Robo\Exception\AbortTasksException;
use Symfony\Component\Console\Input\InputOption;

/**
 * Provides commands to manipulate the Drupal settings.php file.
 */
class DrupalSettingsCommands extends AbstractCommands {

  /**
   * Build the settings.php file from scratch.
   *
   * This command (re)creates a compact settings.php file by using one of the
   * presets defined in the `drupal.settings.presets` config. A preset consist
   * in an ordered list of settings sections. Sections are defined under the
   * `drupal.settings.sections` config.
   *
   * The following configurations are defined under `drupal.settings` and are
   * affecting the way settings.php is built:
   * drupal.settings.presets: Each preset is a list of sections:
   * > drupal:
   * >   settings:
   * >     presets:
   * >       base:
   * >         - Main settings
   * >         - Database
   * >         - ...
   * >       dev:
   * >         - Main settings
   * >         - Database
   * >         - Development settings
   * >         - ...
   * drupal.settings.sections: Settings sections:
   * > drupal:
   * >   settings:
   * >     sections:
   * >       Main settings: |
   * >         $settings['hash_salt'] = getenv('DRUPAL_HASH_SALT');
   * >         $settings['container_yamls'][] =
   * "$app_root/$site_path/services.yml";
   * >         ...
   * >       Database: |
   * >         $databases['default']['default'] = [
   * >           'database' => getenv('DRUPAL_DATABASE_NAME'),
   * >           ...
   * >         ];
   * >         ...
   * drupal.settings.header: The `settings.php` header:
   * > drupal:
   * >   settings:
   * >     header: |
   * >        <?php
   * >        // @file settings.php
   * drupal.settings.footer: The `settings.php` footer:
   * > drupal:
   * >   settings:
   * >     footer: |
   * >       if (file_exists("$app_root/$site_path/settings.override.php")) {
   * >         include "$app_root/$site_path/settings.override.php";
   * >       }
   *
   * @param string $preset
   *   The preset to be used when building the settings file.
   * @param array $options
   *   The command options.
   *
   * @return \Robo\Collection\CollectionBuilder
   *   Collection builder.
   *
   * @throws \Robo\Exception\AbortTasksException
   *   The passed preset is invalid or contains invalid sections.
   *
   * @command drupal:settings
   *
   * @option filename Settings file name.
   * @option root Drupal root.
   * @option sites-subdir Drupal site subdirectory.
   */
  public function settings(string $preset, array $options = [
    'filename' => 'settings.php',
    'root' => InputOption::VALUE_REQUIRED,
    'sites-subdir' => InputOption::VALUE_REQUIRED,
  ]): CollectionBuilder {
    $settingsFile = $this->getSettingsFilePath($options);
    $sections = $this->getSections($preset);

    $taskWriteToFile = $this->taskWriteToFile($settingsFile);

    if ($settingsHeader = $this->getConfig()->get('drupal.settings.header')) {
      $taskWriteToFile->line(trim($settingsHeader));
    }

    foreach ($sections as $section => $content) {
      $taskWriteToFile
        ->lines(['', '', sprintf('// %s.', $section), ''])
        ->line(trim($content));
    }

    if ($settingsFooter = $this->getConfig()->get('drupal.settings.footer')) {
      $taskWriteToFile->lines(['', '', trim($settingsFooter)]);
    }

    return $this->collectionBuilder()->addTask($taskWriteToFile);
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
   * Returns a list of sections belonging to a given preset.
   *
   * @param string $preset
   *   The settings preset.
   *
   * @return array
   *   Associative array with sections belonging to the given preset.
   *
   * @throws \Robo\Exception\AbortTasksException
   *   The passed preset is invalid or contains invalid sections.
   */
  protected function getSections(string $preset): array {
    $allPresets = $this->getConfig()->get('drupal.settings.presets');
    if (!isset($allPresets[$preset])) {
      throw new AbortTasksException("Invalid preset: '{$preset}'.  Check the 'drupal.settings.presets' configuration.");
    }

    $sections = array_flip($allPresets[$preset]);
    $allSections = $this->getConfig()->get('drupal.settings.sections', []);
    $invalidSections = array_diff_key($sections, $allSections);
    if ($invalidSections) {
      throw new AbortTasksException("The '{$preset}' preset contains invalid sections: '" . implode("', '", array_keys($invalidSections)) . "'. Check the 'drupal.settings' configuration.");
    }

    return array_merge($sections, array_intersect_key($allSections, $sections));
  }

}
