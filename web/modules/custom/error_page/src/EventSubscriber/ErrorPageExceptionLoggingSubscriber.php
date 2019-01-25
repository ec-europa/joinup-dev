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
    $uuid_enabled = isset($settings['uuid']['enabled']) ? $settings['uuid']['enabled'] : TRUE;
    $uuid_add_to_message = $uuid_enabled && (isset($settings['uuid']['add_to_message']) ? $settings['uuid']['add_to_message'] : TRUE);

    // Attach the UUID to the the event. Don't call the generator as service.
    $event->uuid = $uuid_enabled ? (new Php())->generate() : NULL;

    $exception = $event->getException();
    $error = Error::decodeException($exception);
    if ($event->uuid) {
      $error += ['@uuid' => $event->uuid];
    }

    if ($uuid_add_to_message) {
      $message = '%type: @message in %function (line %line of %file) [@uuid].';
    }
    else {
      $message = '%type: @message in %function (line %line of %file).';
    }

    $this->logger->get('php')->log($error['severity_level'], $message, $error);

    $is_critical = !$exception instanceof HttpExceptionInterface || $exception->getStatusCode() >= 500;
    if ($is_critical) {
      if ($uuid_add_to_message) {
        $message = 'Uncaught PHP Exception %s: "%s" at %s line %s [@uuid]';
      }
      else {
        $message = 'Uncaught PHP Exception %s: "%s" at %s line %s';
      }
      error_log(sprintf($message, get_class($exception), $exception->getMessage(), $exception->getFile(), $exception->getLine(), $event->uuid));
    }
  }

}
