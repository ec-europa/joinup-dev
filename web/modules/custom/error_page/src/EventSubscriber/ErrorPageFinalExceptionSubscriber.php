<?php

namespace Drupal\error_page\EventSubscriber;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\EventSubscriber\FinalExceptionSubscriber;
use Drupal\Core\Utility\Error;
use Drupal\error_page\ErrorPageRenderer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscribes to KernelEvents::EXCEPTION events.
 */
class ErrorPageFinalExceptionSubscriber extends FinalExceptionSubscriber {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      // This subscriber steals the show by acting just before the core
      // FinalExceptionSubscriber, which has the priority set to -256.
      // @see \Drupal\Core\EventSubscriber\FinalExceptionSubscriber::getSubscribedEvents()
      KernelEvents::EXCEPTION => ['onException', -255],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function onException(GetResponseForExceptionEvent $event) {
    $exception = $event->getException();
    $error = Error::decodeException($exception);

    // Generate an error report if the current error reporting level allows this
    // type of report to be displayed and unconditionally in update.php.
    $error_report = '';
    if ($this->isErrorDisplayable($error)) {
      // If error type is 'User notice' then treat it as debug information
      // instead of an error message.
      // @see debug()
      if ($error['%type'] == 'User notice') {
        $error['%type'] = 'Debug';
      }

      $error = $this->simplifyFileInError($error);

      unset($error['backtrace']);

      if (!$this->isErrorLevelVerbose()) {
        // Without verbose logging, use a simple message. Use FormattableMarkup
        // directly here, rather than use t() since we are in the middle of
        // error handling, and we don't want t() to cause further errors.
        $error_report = new FormattableMarkup('%type: @message in %function (line %line of %file).', $error);
      }
      else {
        // With verbose logging, we will also include a backtrace.
        $backtrace_exception = $exception;
        while ($backtrace_exception->getPrevious()) {
          $backtrace_exception = $backtrace_exception->getPrevious();
        }
        $backtrace = $backtrace_exception->getTrace();
        // First trace is the error itself, already contained in the message.
        // While the second trace is the error source and also contained in the
        // message, the message doesn't contain argument values, so we output it
        // once more in the backtrace.
        array_shift($backtrace);

        // Generate a backtrace containing only scalar argument values.
        $error['@backtrace'] = Error::formatBacktrace($backtrace);
        $error_report = new FormattableMarkup('%type: @message in %function (line %line of %file). <pre class="backtrace">@backtrace</pre>', $error);
      }
    }

    // Require explicitly the renderer class, as the container might not be
    // available yet and, as a consequence, the auto-loading might not work for
    // extensions such as modules.
    require_once __DIR__ . '/../ErrorPageRenderer.php';

    $uuid = !empty($event->uuid) ? $event->uuid : NULL;
    $markup = ErrorPageRenderer::render('page', $uuid, $exception, $error_report);
    $response = new Response($markup, 500, ['Content-Type' => 'text/html']);

    if ($exception instanceof HttpExceptionInterface) {
      $response->setStatusCode($exception->getStatusCode());
      $response->headers->add($exception->getHeaders());
    }
    else {
      $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR, '500 Service unavailable (with message)');
    }

    $event->setResponse($response);
  }

}
