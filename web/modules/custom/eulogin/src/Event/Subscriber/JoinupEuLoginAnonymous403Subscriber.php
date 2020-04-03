<?php

declare(strict_types = 1);

namespace Drupal\joinup_eulogin\Event\Subscriber;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableRedirectResponse;
use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/**
 * Redirects anonymous 403 pages to EU Login login page.
 */
class JoinupEuLoginAnonymous403Subscriber extends HttpExceptionSubscriberBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new subscriber instance.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  protected function getHandledFormats() {
    return ['html'];
  }

  /**
   * Redirects anonymous to EU Login login on '403 Access Denied' exceptions.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on403(GetResponseForExceptionEvent $event) {
    if (!$this->currentUser->isAnonymous()) {
      return;
    }

    $request = $event->getRequest();
    $destination = substr($request->getPathInfo(), 1);
    if ($query_string = $request->getQueryString()) {
      $destination .= '?' . $query_string;
    }

    // Build the redirect URL options.
    $options = [
      'absolute' => TRUE,
      'query' => [
        'returnto' => $destination,
      ],
    ];
    $redirect_url = Url::fromRoute('cas.login', [], $options)->toString();

    $response = new CacheableRedirectResponse($redirect_url);

    $cache_metadata = new CacheableMetadata();

    // Vary the redirect cache by URL and if the user is anonymous or not.
    $cache_metadata->addCacheContexts(['url', 'user.roles:anonymous']);

    // Copy the original cache metadata associated with the exception. The
    // exception itself received the cache metadata from the route access check.
    // @see \Drupal\Core\Routing\AccessAwareRouter::checkAccess()
    if ($event->getException() instanceof CacheableDependencyInterface) {
      $cache_metadata->addCacheableDependency($event->getException());
    }

    $response->addCacheableDependency($cache_metadata);

    $event->setResponse($response);
  }

}
