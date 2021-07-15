<?php

declare(strict_types = 1);

namespace Drupal\joinup_event\EventSubscriber;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Routing\LocalRedirectResponse;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber for events.
 *
 * What do you mean, this is confusing?
 */
class JoinupEventEventSubscriber implements EventSubscriberInterface {

  /**
   * The inbound path processor.
   *
   * @var \Drupal\Core\PathProcessor\InboundPathProcessorInterface
   */
  protected $pathProcessor;

  /**
   * Constructs an event subscriber for events.
   *
   * @param \Drupal\Core\PathProcessor\InboundPathProcessorInterface $pathProcessor
   *   The inbound path processor.
   */
  public function __construct(InboundPathProcessorInterface $pathProcessor) {
    $this->pathProcessor = $pathProcessor;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      // This needs to run before RouterListener::onKernelRequest(), which has a
      // priority of 32. Otherwise, that aborts the request if no matching route
      // is found.
      KernelEvents::REQUEST => [['onKernelRequest', 33]],
    ];
  }

  /**
   * Redirects /events to the search page, filtered on events.
   *
   * Until the Events page is ready we temporarily redirect to the search page.
   * This way our users can already find the events they are looking for.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   Response event.
   */
  public function onKernelRequest(GetResponseEvent $event) {
    // Retrieve the request path, and do inbound processing so that language
    // prefixes are removed.
    $request = $event->getRequest();
    if (strpos($request->getPathInfo(), '/system/files/') === 0 && !$request->query->has('file')) {
      // Private files paths are split by the inbound path processor and the
      // relative file path is moved to the 'file' query string parameter. This
      // is because the route system does not allow an arbitrary amount of
      // parameters. We preserve the path as is returned by the request object.
      // @see \Drupal\system\PathProcessor\PathProcessorFiles::processInbound()
      // @see \Drupal\redirect\EventSubscriber\RedirectRequestSubscriber::onKernelRequestCheckRedirect()
      $path = $request->getPathInfo();
    }
    else {
      $path = $this->pathProcessor->processInbound($request->getPathInfo(), $request);
    }

    if ($path === '/events') {
      $url = Url::fromRoute('view.search.page_1');
      $url->setOption('query', ['f' => ['type:event']]);
      $response = new LocalRedirectResponse($url->toString(), Response::HTTP_FOUND);
      $event->setResponse($response);
    }
  }

}
