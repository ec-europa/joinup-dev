<?php

declare(strict_types = 1);

namespace DrupalProject\CodingStandards\Sniffs\NamingConventions;

use Composer\Semver\Comparator;
use Drupal\Component\Serialization\Yaml;
use Drupal\Sniffs\NamingConventions\ValidFunctionNameSniff;
use GitWrapper\GitWrapper;
use PHP_CodeSniffer\Files\File;

/**
 * Checks the naming of (post)update functions.
 *
 * Joinup offers only contiguous upgrades. If a project is currently on Joinup
 * `v1.39.2`, and the latest stable version is `v1.42.0`, then upgrading
 * directly to the latest version is not possible. Instead, the project should
 * upgrade to each of the intermediate versions. First to `v1.40.0`, second to
 * `v1.40.1` (if exists) and, finally, to `v1.42.0`.
 *
 * The Joinup update and post-update scripts naming should follow this pattern:
 * @code
 * function joinup_update_016002() {...}
 * @encode
 * or
 * @code
 * function joinup_post_update_016002() {...}
 * @endcode
 * The variable part consists in six digits with the following meaning:
 * - The first two digits (`01`) are the Joinup major version.
 * - The following two digits (`06`) are the Joinup minor version.
 * - The last two digits (`02`) are the Joinup patch version.
 *
 * In the above example the update, and the post-update were applied in Joinup
 * release `v1.60.2` against the latest version. Note that you can have the same
 * update and post-update number and even the same (post)update number on
 * different extensions (modules or profile).
 *
 * This sniff checks if the (post)update is correctly formed and if the version
 * represented by the (post)update identifier was not already released.
 */
class ValidUpdateNameSniff extends ValidFunctionNameSniff {

  /**
   * Static cache for the list of enabled extensions.
   *
   * @var string[]
   */
  protected static $enabledExtensions;

  /**
   * Static cache for Git latest tag.
   *
   * @var string
   */
  protected static $gitDescribeTag;

  /**
   * {@inheritdoc}
   */
  protected function processTokenOutsideScope(File $phpcsFile, $stackPtr) {
    $functionName = $phpcsFile->getDeclarationName($stackPtr);

    if ($functionName === NULL || !preg_match('#_(post_)?update_#', $functionName)) {
      // Ignore closures and functions that cannot be (post)updates.
      return;
    }

    $match = FALSE;
    foreach (static::getEnabledExtensions() as $extension) {
      if (preg_match("#^{$extension}_(post_)?update_(.*)$#", $functionName, $found)) {
        $match = TRUE;
        $name = $found[2];
        $updateType = empty($found[1]) ? 'update' : 'post update';
      }
    }

    // Not a (post)update.
    if (!$match) {
      return;
    }

    $error = NULL;
    $data = [$functionName, $updateType];
    if (!ctype_digit($name) || strlen($name) !== 6) {
      $error = "Remove %s(). This %s was applied prior v1.59.2.";
    }
    else {
      // Transform the name into a tag.
      $tag = (int) substr($name, 0, 2) . '.' . (int) substr($name, 2, 2) . '.' . (int) substr($name, 4);

      if (Comparator::lessThan($tag, static::getGitDescribeTag())) {
        $error = "Remove %s(). This %s was applied in v%s.";
        $data[] = $tag;
      }
    }

    if ($error) {
      $phpcsFile->addError($error, $stackPtr, 'InvalidUpdateId', $data);
    }
  }

  /**
   * Returns the Git describe tag.
   *
   * The return value is a Git tag, if coincides with the current commit or the
   * latest tag followed by a dash and the number of commits since the latest
   * tag.
   *
   * @return string
   *   The Git describe tag with the potential leading 'v' stripped out.
   */
  protected static function getGitDescribeTag(): string {
    if (!isset(static::$gitDescribeTag)) {
      $wrapper = new GitWrapper();
      $workingCopy = $wrapper->workingCopy(getcwd());
      $tag = trim((string) $workingCopy->run(['describe --tags']));
      // Remove a potential leading 'v'.
      static::$gitDescribeTag = ltrim($tag, 'v');
    }
    return static::$gitDescribeTag;
  }

  /**
   * Returns all enabled extensions.
   *
   * @return string[]
   *   Enabled extensions.
   */
  protected static function getEnabledExtensions(): array {
    if (!isset(static::$enabledExtensions)) {
      static::$enabledExtensions = array_keys(Yaml::decode(file_get_contents(getcwd() . '/config/sync/core.extension.yml'))['module']);
    }
    return static::$enabledExtensions;
  }

}
