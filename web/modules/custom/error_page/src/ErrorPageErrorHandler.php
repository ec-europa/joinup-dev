<?php

namespace Drupal\error_page;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Component\Uuid\Php;
use Drupal\Core\Render\Markup;
use Drupal\Core\Site\Settings;
use Drupal\Core\Utility\Error;
use Symfony\Component\HttpFoundation\Response;

/**
 * Custom fatal error handler.
 */
class ErrorPageErrorHandler {

  /**
   * The exception/error UUID, if any.
   *
   * @var string|null
   */
  protected static $uuid;

  /**
   * If the UUID is injected in the error message.
   *
   * @var bool
   */
  protected static $uuidAddToMessage;

  /**
   * Handles errors.
   *
   * Code copied and adapted from _drupal_error_handler_real().
   *
   * @param int $level
   *   Error level.
   * @param string $message
   *   Error message.
   * @param string $file
   *   Error file.
   * @param int $line
   *   Error line in file.
   * @param array $context
   *   Error context.
   *
   * @see _drupal_error_handler_real()
   */
  public static function handleError($level, $message, $file, $line, array $context) {
    require_once DRUPAL_ROOT . '/core/includes/errors.inc';

    if ($level & error_reporting()) {
      $types = drupal_error_levels();
      list($severity_msg, $severity_level) = $types[$level];
      $backtrace = debug_backtrace();
      $caller = Error::getLastCaller($backtrace);

      // We treat recoverable errors as fatal.
      $recoverable = $level == E_RECOVERABLE_ERROR;
      // As __toString() methods must not throw exceptions (recoverable errors)
      // in PHP, we allow them to trigger a fatal error by emitting a user error
      // using trigger_error().
      $to_string = $level == E_USER_ERROR && substr($caller['function'], -strlen('__toString()')) == '__toString()';
      static::logError([
        '%type' => isset($types[$level]) ? $severity_msg : 'Unknown error',
        // The standard PHP error handler considers that the error messages
        // are HTML. We mimick this behavior here.
        '@message' => Markup::create(Xss::filterAdmin($message)),
        '%function' => $caller['function'],
        '%file' => $caller['file'],
        '%line' => $caller['line'],
        'severity_level' => $severity_level,
        'backtrace' => $backtrace,
        '@backtrace_string' => (new \Exception())->getTraceAsString(),
      ], $recoverable || $to_string);
    }
    // If the site is a test site then fail for user deprecations so they can be
    // caught by the deprecation error handler.
    elseif (DRUPAL_TEST_IN_CHILD_SITE && $level === E_USER_DEPRECATED) {
      $backtrace = debug_backtrace();
      $caller = Error::getLastCaller($backtrace);
      _drupal_error_header(
        Markup::create(Xss::filterAdmin($message)),
        'User deprecated function',
        $caller['function'],
        $caller['file'],
        $caller['line']
      );
    }
  }

  /**
   * Handles exceptions.
   *
   * Code copied and adapted from _drupal_exception_handler().
   *
   * @param \Throwable $exception
   *   The throwable.
   *
   * @see _drupal_exception_handler()
   */
  public static function handleException(\Throwable $exception) {
    require_once DRUPAL_ROOT . '/core/includes/errors.inc';

    try {
      // Log the message to the watchdog and return an error page to the user.
      static::logError(Error::decodeException($exception), TRUE, $exception);
    }
    // PHP 7 introduces Throwable, which covers both Error and
    // Exception throwables.
    catch (\Throwable $error) {
      _drupal_exception_handler_additional($exception, $error);
    }
    // In order to be compatible with PHP 5 we also catch regular Exceptions.
    catch (\Exception $exception2) {
      _drupal_exception_handler_additional($exception, $exception2);
    }
  }

  /**
   * Logs a PHP error or exception and displays an error page in fatal cases.
   *
   * This method is a slight changed version of _drupal_log_error().
   *
   * @param array $error
   *   An array with the following keys: %type, @message, %function, %file,
   *   %line, @backtrace_string, severity_level, and backtrace. All the
   *   parameters are plain-text, with the exception of @message, which needs to
   *   be an HTMLstring, and backtrace, which is a standard PHP backtrace.
   * @param bool $fatal
   *   TRUE for:
   *   - An exception is thrown and not caught by something else.
   *   - A recoverable fatal error, which is a fatal error.
   *   Non-recoverable fatal errors cannot be logged by Drupal.
   * @param \Throwable|null $original_exception
   *   The original exception.
   *
   * @see _drupal_log_error()
   */
  protected static function logError(array $error, $fatal = FALSE, $original_exception = NULL) {
    $settings = Settings::get('error_page');
    $uuid_enabled = isset($settings['uuid']['enabled']) ? $settings['uuid']['enabled'] : TRUE;
    static::$uuidAddToMessage = $uuid_enabled && (isset($settings['uuid']['add_to_message']) ? $settings['uuid']['add_to_message'] : TRUE);
    static::$uuid = $uuid_enabled ? (new Php())->generate() : NULL;

    if (static::$uuid) {
      $error += ['@uuid' => static::$uuid];
    }

    $is_installer = drupal_installation_attempted();

    // Backtrace array is not a valid replacement value for t().
    $backtrace = $error['backtrace'];
    unset($error['backtrace']);

    // When running inside the testing framework, we relay the errors
    // to the tested site by the way of HTTP headers.
    if (DRUPAL_TEST_IN_CHILD_SITE && !headers_sent() && (!defined('SIMPLETEST_COLLECT_ERRORS') || SIMPLETEST_COLLECT_ERRORS)) {
      _drupal_error_header($error['@message'], $error['%type'], $error['%function'], $error['%file'], $error['%line']);
    }

    $response = new Response();

    // Only call the logger if there is a logger factory available. This can
    // occur if there is an error while rebuilding the container or during the
    // installer.
    if (\Drupal::hasService('logger.factory')) {
      try {
        // Provide the PHP backtrace to logger implementations.
        \Drupal::logger('php')->log($error['severity_level'], static::getMessage('standard_with_backtrace'), $error + ['backtrace' => $backtrace]);
      }
      catch (\Exception $e) {
        // We can't log, for example because the database connection is not
        // available. At least try to log to PHP error log.
        error_log(strtr(static::getMessage('failed_to_log'), $error));
      }
    }

    // Log fatal errors, so developers can find and debug them.
    if ($fatal) {
      error_log(sprintf(static::getMessage('fatal'), $error['%type'], $error['@message'], $error['%file'], $error['%line'], $error['@backtrace_string']));
    }

    if (PHP_SAPI === 'cli') {
      if ($fatal) {
        // When called from CLI, simply output a plain text message. Should not
        // translate the string to avoid errors producing more errors.
        $response->setContent(html_entity_decode(strip_tags(new FormattableMarkup(static::getMessage('standard'), $error))) . "\n");
        $response->send();
        exit;
      }
    }

    if (\Drupal::hasRequest() && \Drupal::request()->isXmlHttpRequest()) {
      if ($fatal) {
        if (error_displayable($error)) {
          // When called from JavaScript, simply output the error message.
          // Should not translate the string to avoid errors producing more
          // errors.
          $response->setContent(new FormattableMarkup(static::getMessage('standard'), $error));
          $response->send();
        }
        exit;
      }
    }
    else {
      // Display the message if the current error reporting level allows this
      // type of message to be displayed, and unconditionally in update.php.
      $message = '';
      $class = NULL;
      if (error_displayable($error)) {
        $class = 'error';

        // If error type is 'User notice' then treat it as debug information
        // instead of an error message.
        // @see debug()
        if ($error['%type'] == 'User notice') {
          $error['%type'] = 'Debug';
          $class = 'status';
        }

        // Attempt to reduce verbosity by removing DRUPAL_ROOT from the file
        // path in the message. This does not happen for (false) security.
        if (\Drupal::hasService('app.root')) {
          $root_length = strlen(\Drupal::root());
          if (substr($error['%file'], 0, $root_length) == \Drupal::root()) {
            $error['%file'] = substr($error['%file'], $root_length + 1);
          }
        }

        // Require explicitly the renderer class, as the container might not be
        // available yet and, as a consequence, the auto-loading might not work
        // for extensions such as modules.
        require_once __DIR__ . '/ErrorPageRenderer.php';
        $markup = ErrorPageRenderer::render('message', static::$uuid, $original_exception);
        $message = new FormattableMarkup($markup, $error);
      }

      if ($fatal) {
        if ($is_installer) {
          $message = 'The website encountered an unexpected error. Please try again later.' . '<br />' . $message;
          // install_display_output() prints the output and ends script
          // execution.
          $output = [
            '#title' => 'Error',
            '#markup' => $message,
          ];
          install_display_output($output, $GLOBALS['install_state'], $response->headers->all());
          exit;
        }

        if (!$original_exception) {
          // Create one if we come from an error.
          $original_exception = new \Exception($error['@message']);
        }

        // Require explicitly the renderer class, as the container might not be
        // available yet and, as a consequence, the auto-loading might not work
        // for extensions such as modules.
        require_once __DIR__ . '/ErrorPageRenderer.php';
        $markup = ErrorPageRenderer::render('page', static::$uuid, $original_exception);

        $response->setContent($markup);
        $response->setStatusCode(500, '500 Service unavailable (with message)');

        $response->send();
        // An exception must halt script execution.
        exit;
      }

      if ($message) {
        if (\Drupal::hasService('session')) {
          // Message display is dependent on sessions being available.
          \Drupal::messenger()->addMessage($message, $class, TRUE);
        }
        else {
          print $message;
        }
      }
    }
  }

  /**
   * Returns an error message of a given type.
   *
   * @param string $type
   *   The message type.
   *
   * @return string
   *   The message text.
   */
  protected static function getMessage($type) {
    $messages = [
      FALSE => [
        'standard' => '%type: @message in %function (line %line of %file).',
        'standard_with_backtrace' => '%type: @message in %function (line %line of %file) @backtrace_string',
        'failed_to_log' => 'Failed to log error: %type: @message in %function (line %line of %file). @backtrace_string',
        'fatal' => '%s: %s in %s on line %d %s',
      ],
      TRUE => [
        'standard' => '%type: @message in %function (line %line of %file) [' . static::$uuid . '].',
        'standard_with_backtrace' => '%type: @message in %function (line %line of %file) [' . static::$uuid . '] @backtrace_string',
        'failed_to_log' => 'Failed to log error: %type: @message in %function (line %line of %file) [' . static::$uuid . ']. @backtrace_string',
        'fatal' => '%s: %s in %s on line %d [' . static::$uuid . '] %s',
      ],
    ];
    return $messages[static::$uuidAddToMessage][$type];
  }

}
