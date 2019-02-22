<?php

namespace Drupal\error_page\EventSubscriber;

use Drupal\Component\Uuid\Php;
use Drupal\Core\EventSubscriber\ExceptionLoggingSubscriber;
use Drupal\Core\Site\Settings;
use Drupal\Core\Utility\Error;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Overrides the core 'exception.logger' service's class.
 */
class ErrorPageExceptionLoggingSubscriber extends ExceptionLoggingSubscriber {

  /**
   * {@inheritdoc}
   */
  public function onError(GetResponseForExceptionEvent $event) {
    $settings = Settings::get('error_page');
    $uuid_enabled = isset($settings['uuid']) ? $settings['uuid'] : TRUE;

    // Attach the UUID to the the event. Don't call the generator as service.
    $event->uuid = $uuid_enabled ? (new Php())->generate() : NULL;

    $exception = $event->getException();
    $error = Error::decodeException($exception);
    if ($event->uuid) {
      $error += ['@uuid' => $event->uuid];
    }

    $this->logger->get('php')->log($error['severity_level'], '%type: @message in %function (line %line of %file) [@uuid].', $error);

    $is_critical = !$exception instanceof HttpExceptionInterface || $exception->getStatusCode() >= 500;
    if ($is_critical) {
      error_log(sprintf('Uncaught PHP Exception %s: "%s" at %s line %s [%s]', get_class($exception), $exception->getMessage(), $exception->getFile(), $exception->getLine(), $event->uuid));
    }
  }

}
