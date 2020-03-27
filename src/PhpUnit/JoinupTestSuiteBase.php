<?php

declare(strict_types = 1);

namespace DrupalProject\PhpUnit;

use Drupal\Core\Test\TestDiscovery;
use Drupal\Tests\TestSuites\TestSuiteBase;

require_once __DIR__ . '/../../web/core/tests/TestSuites/TestSuiteBase.php';

/**
 * Base class for Joinup test suites.
 */
abstract class JoinupTestSuiteBase extends TestSuiteBase {

  /**
   * {@inheritdoc}
   */
  protected function findExtensionDirectories($root): array {
    $extension_roots = [
      "$root/modules/custom",
      "$root/themes",
      "$root/profiles",
    ];
    $extension_directories = array_map('drupal_phpunit_find_extension_directories', $extension_roots);
    return array_reduce($extension_directories, 'array_merge', []);
  }

  /**
   * {@inheritdoc}
   */
  protected function addTestsBySuiteNamespace($root, $suite_namespace): void {
    $root = dirname(dirname(__DIR__)) . '/web';
    foreach ($this->findExtensionDirectories($root) as $extension_name => $dir) {
      $test_path = "$dir/tests/src/$suite_namespace";
      if (is_dir($test_path)) {
        $this->addTestFiles(TestDiscovery::scanDirectory("Drupal\\Tests\\$extension_name\\$suite_namespace\\", $test_path));
      }
    }
  }

}
