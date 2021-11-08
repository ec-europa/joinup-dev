<?php

declare(strict_types = 1);

namespace Joinup\CodingStandards\Sniffs\NamingConventions;

use Composer\Semver\Comparator;
use Drupal\Component\Serialization\Yaml;
use Drupal\Sniffs\NamingConventions\ValidFunctionNameSniff;
use Gitonomy\Git\Repository;
use PHP_CodeSniffer\Files\File;

/**
 * Checks the naming of (post)update functions.
 *
 * Joinup offers only _contiguous upgrades_. For instance, if you project is
 * currently on Joinup `v1.39.2`, and the latest stable version is `v1.42.0`,
 * then you cannot upgrade directly to the latest version. Instead, you should
 * upgrade first to `v1.40.0`, second to `v1.40.1` (if exists) and, finally, to
 * `v1.42.0`.
 *
 * The Joinup update, post-update and deploy scripts naming is following this
 * pattern:
 * @code
 * function mymodule_update_0106100() {...}
 * @endcode
 * or
 * @code
 * function mymodule_post_update_0207503() {...}
 * @endcode
 * or
 * @code
 * function mymodule_deploy_0208101() {...}
 * @endcode
 * The (post)updated identifier (the numeric part consists in seven digits with
 * the following meaning:
 * - The first two digits are the Joinup major version.
 * - The following three digits are the Joinup minor version.
 * - The last two digits are an integer that sets the weight within updates or
 *   post updates from the same extension (module or profile). `00` is the first
 *   (post)update that applies.
 *
 * Given the above example:
 *
 * `function mymodule_update_0106100() {...}`: Was applied in Joinup `v1.61.x`
 * as the first update of the `mymodule` module (`01` major version, `061` minor
 * version, `00` update weight within the module).
 * `function mymodule_post_update_0207503() {...}`: Was applied in Joinup
 * `v2.75.x` as the fourth post update of the `mymodule` module (`02` major
 * version, `075` minor version, `03` update weight within the module).
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
  protected static array $enabledExtensions;

  /**
   * Static cache for latest stable Git tag.
   *
   * @var string
   */
  protected static string $latestStableTag;

  /**
   * Static cache for next version candidates.
   *
   * @var string[]
   */
  protected static array $nextVersionCandidates;

  /**
   * {@inheritdoc}
   */
  protected function processTokenOutsideScope(File $phpcsFile, $stackPtr): void {
    $functionName = $phpcsFile->getDeclarationName($stackPtr);

    if ($functionName === NULL || !preg_match('#_((post_)?update|deploy)_#', $functionName)) {
      // Ignore closures and functions that cannot be (post)updates.
      return;
    }

    $match = FALSE;
    foreach (static::getEnabledExtensions() as $extension) {
      if (preg_match("#^{$extension}_((post_)?update|deploy)_(.*)$#", $functionName, $found)) {
        $match = TRUE;
        $name = $found[3];
        $updateType = !empty($found[2]) ? 'post update' : $found[1];
        break;
      }
    }

    // Not a (post)update.
    if (!$match) {
      return;
    }

    $error = NULL;
    if (!ctype_digit($name) || strlen($name) !== 7) {
      $error = "Invalid '%s' identifier. Expected 7 digits:\n- 2 digits: Joinup major version\n- 3 digits: Joinup minor version\n- 2 digits: The %s weight";
      $data = [$name, $updateType];
    }
    else {
      $majorVersion = (int) substr($name, 0, 2);
      $minorVersion = (int) substr($name, 2, 3);

      $minorVersionTag = "{$majorVersion}.{$minorVersion}";
      $tag = "{$minorVersionTag}.0";

      if (Comparator::lessThan($tag, static::getLatestStableTag())) {
        $error = "Remove %s(). Already applied in %s";
        $data = [$functionName, $minorVersionTag];
      }
      else {
        $nextVersionCandidates = static::getNextVersionCandidates();
        if (!in_array(substr($name, 0, 5), $nextVersionCandidates)) {
          $error = "Invalid '%s' identifier. The first 5 digits should be '%s' or '%s'";
          $data = [$name, $nextVersionCandidates[0], $nextVersionCandidates[1]];
        }
      }
    }

    if ($error) {
      $phpcsFile->addError($error, $stackPtr, 'InvalidUpdateId', $data);
    }
  }

  /**
   * Returns the latest semver valid and stable Git tag.
   *
   * @return string
   *   The latest semver valid and stable Git tag with the potential leading 'v'
   *   stripped out.
   */
  protected static function getLatestStableTag(): string {
    if (!isset(static::$latestStableTag)) {
      $repository = new Repository(getcwd());
      $tagsString = trim($repository->run('tag', ['--sort=creatordate']));
      $tags = array_reverse(preg_split('/\s/', $tagsString));
      foreach ($tags as $tag) {
        // Only support stable release tags. The regexp is borrowed and adapted
        // from \Composer\Semver\VersionParser::normalize().
        if (preg_match('{^v?(\d{1,5})(\.\d++)?(\.\d++)?(\.\d++)?$}i', $tag)) {
          // Remove a potential leading 'v'.
          static::$latestStableTag = ltrim($tag, 'v');
          break;
        }
      }
    }

    return static::$latestStableTag;
  }

  /**
   * Returns a list of possible next version.
   *
   * @return array
   *   A two elements array.
   */
  protected static function getNextVersionCandidates(): array {
    if (!isset(static::$nextVersionCandidates)) {
      [$majorVersion, $minorVersion] = explode('.', static::getLatestStableTag());
      static::$nextVersionCandidates = [
        // Next minor version.
        sprintf('%02d%03d', $majorVersion, ++$minorVersion),
        // Next major version.
        sprintf('%02d%03d', ++$majorVersion, 0),
      ];
    }
    return self::$nextVersionCandidates;
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
