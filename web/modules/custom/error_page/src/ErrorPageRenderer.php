<?php

namespace Drupal\error_page;

use Drupal\Core\Site\Settings;

/**
 * Renders the custom error page or message.
 */
class ErrorPageRenderer {

  /**
   * Renders the error page or message as HTML markup.
   *
   * The Drupal/Twig rendering mechanism cannot be used as this would require
   * Drupal services to be available. Uncaught exceptions indicates that
   * something really wrong has happened on a site. There is a great chance that
   * some, or all, Drupal services are not available, so rendering the error
   * page may fail as well. For this reason we're using a raw mechanism that
   * simply reads a HTML file and replaces some variable tokens.
   *
   * The files shipped with the module are:
   * - markup/error_page.html: for rendering HTML pages in the case of uncaught
   *   exceptions.
   * - markup/error_message.html: for rendering the markup being displayed in a
   *   user error status message.
   *
   * In order to customise and style the two files, copy them in any other
   * directory. Configure the $settings['error_page']['template_dir'], from the
   * settings.php file to value to point to that directory.
   *
   * Note:
   * - The following variables can be used in the HTML markup:
   *   - {{ uuid }}: The error/exception UUID, if any.
   *   - {{ base_path }}: The Drupal base path, as is returned by base_path().
   *     It helps to build paths to images or other assets.
   * - It's recommended that the custom template location is placed outside the
   *   web-tree or is protected from the web-server public access with a file,
   *   similar to markup/.htaccess.
   * - The reason why this module is using $settings values, instead of the
   *   standard Drupal configuration system, is because, when a error occurs,
   *   some of the Drupal services, including the configuration factory or
   *   related, might not be available.
   *
   * @param string $type
   *   The type of markup to be rendered, either 'page' or 'message'.
   * @param string|null $uuid
   *   The exception UUID, if any.
   * @param mixed $original_exception
   *   The original exception. Is used if an additional exception occurs during
   *   handling the current error.
   *
   * @return string
   *   The rendered HTML markup.
   *
   * @see \base_path()
   * @see markup/error_page.html
   */
  public static function render($type, $uuid, $original_exception) {
    try {
      $settings = Settings::get('error_page');
      $module_dir = __DIR__ . '/../markup';
      $has_custom_path = !empty($settings['template_dir']);
      $path = $has_custom_path ? $settings['template_dir'] : $module_dir;
      $file_path = "$path/error_{$type}.html";
      // Maybe only the other file has been customised.
      if ($has_custom_path && !file_exists($file_path)) {
        $file_path = "$module_dir/error_{$type}.html";
      }
      $markup = trim(file_get_contents($file_path));

      // @todo Use preg_replace() to catch also the spacing fuzziness.
      return str_replace(['{{ uuid }}', '{{ base_path }}'], [
        $uuid,
        \base_path(),
      ], $markup);
    }
    catch (\Throwable $exception) {
      _drupal_exception_handler_additional($original_exception, $exception);
      exit;
    }
    catch (\Exception $exception2) {
      _drupal_exception_handler_additional($original_exception, $exception2);
      exit();
    }
  }

}
