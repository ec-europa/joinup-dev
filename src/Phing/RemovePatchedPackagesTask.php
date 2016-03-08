<?php

/**
 * @file
 * Contains \DrupalProject\build\Phing\RemovePatchedPackagesTask.
 */

namespace DrupalProject\Phing;

use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Package\AliasPackage;

require_once 'phing/Task.php';

/**
 * A Phing task that removes all patched Composer packages.
 *
 * It will inspect composer.json to find the packages that are patched by
 * cweagans/composer-patches and remove them, so they can be reinstalled.
 */
class RemovePatchedPackagesTask extends \Task {

  /**
   * The location of the composer.json file.
   *
   * @var string
   */
  private $composerJsonPath;

  /**
   * Removes patched packages.
   */
  public function main() {
    // Check if all required data is present.
    $this->checkRequirements();

    $composer = Factory::create(new NullIO(), $this->composerJsonPath);

    // Force discarding of changes, these packages are patched after all.
    // @todo Make this configurable with a "force" flag.
    $config = $composer->getConfig();
    $config->merge(['config' => ['discard-changes' => TRUE]]);

    // Get the list of patches.
    $extra = $composer->getPackage()->getExtra();
    if (!empty($extra['patches'])) {
      $repository = $composer->getRepositoryManager()->getLocalRepository();
      $installation_manager = $composer->getInstallationManager();

      // Loop over the patched packages.
      foreach (array_keys($extra['patches']) as $package_name) {
        foreach ($repository->findPackages($package_name) as $package) {
          // Skip aliases, only remove the actual packages.
          if (!$package instanceof AliasPackage) {
            // Remove the package.
            $this->log("Removing patched package '$package_name'.");
            $operation = new UninstallOperation($package, 'Uninstalling patched package so it can be reinstalled.');
            $installation_manager->uninstall($repository, $operation);
          }
        }
      }
      // Re-generate the autoloader to get rid of stale class definitions.
      $generator = $composer->getAutoloadGenerator();
      $localRepo = $composer->getRepositoryManager()->getLocalRepository();
      $package = $composer->getPackage();
      $installationManager = $composer->getInstallationManager();
      $generator->dump($config, $localRepo, $package, $installationManager, 'composer');
    }
  }

  /**
   * Checks if the Composer config file exists.
   *
   * @throws \BuildException
   *   Thrown when the config file does not exist.
   */
  protected function checkRequirements() {
    if (empty($this->composerJsonPath) || !file_exists($this->composerJsonPath)) {
      throw new \BuildException("The path specified for 'composerJsonPath' doesn't exist.");
    }
  }

  /**
   * Sets the path to the Composer configuration file.
   *
   * @param string $composerJsonPath
   *   The path to the configuration file.
   */
  public function setComposerJsonPath($composerJsonPath) {
    $this->composerJsonPath = $composerJsonPath;
  }

}
