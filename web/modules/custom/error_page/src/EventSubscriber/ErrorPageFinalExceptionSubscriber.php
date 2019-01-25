<?php

namespace Drupal\error_page\EventSubscriber;

use Drupal\error_page\ErrorPageRenderer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscribes to KernelEvents::EXCEPTION events.
 */
class ErrorPageFinalExceptionSubscriber implements EventSubscriberInterface {

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
    $uuid = !empty($event->uuid) ? $event->uuid : NULL;
    $exception = $event->getException();

    // Require explicitly the renderer class, as the container might not be
    // available yet and, as a consequence, the auto-loading might not work for
    // extensions such as modules.
    require_once __DIR__ . '/../ErrorPageRenderer.php';
    $markup = ErrorPageRenderer::render('page', $uuid, $exception);

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
