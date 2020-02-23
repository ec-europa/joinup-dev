<?php

declare(strict_types = 1);

namespace Drupal\joinup_eulogin\Event\Subscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Routing\LocalRedirectResponse;
use Drupal\Core\Routing\ResettableStackedRouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\externalauth\AuthmapInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Listens to kernel events.
 */
class JoinupEuLoginKernelSubscriber implements EventSubscriberInterface {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\ResettableStackedRouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The external authentication map service.
   *
   * @var \Drupal\externalauth\AuthmapInterface
   */
  protected $authmap;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new event subscriber instance.
   *
   * @param \Drupal\Core\Routing\ResettableStackedRouteMatchInterface $route_match
   *   The current route match service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\externalauth\AuthmapInterface $authmap
   *   The external authentication map service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(ResettableStackedRouteMatchInterface $route_match, AccountProxyInterface $current_user, AuthmapInterface $authmap, StateInterface $state) {
    $this->routeMatch = $route_match;
    $this->currentUser = $current_user;
    $this->authmap = $authmap;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      // Run before 'entity_legal.subscriber', so that the user will not get the
      // legal notice instead of the 'Limited access' page.
      KernelEvents::REQUEST => [['redirectWhenNoAccess', 10]],
      KernelEvents::RESPONSE => 'setAccessDeniedCode',
    ];
  }

  /**
   * Sets the response code to 403 when accessing /user/limited-access page.
   *
   * It's not enough to show the 'Limited access' page to the user, we have some
   * appropriate response also for the browser.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The response event object.
   */
  public function setAccessDeniedCode(FilterResponseEvent $event): void {
    if ($this->limitedAccessIsDisabled()) {
      return;
    }

    $route_match = $this->routeMatch->getRouteMatchFromRequest($event->getRequest());
    if ($route_match->getRouteName() === 'joinup_eulogin.page.limited_access') {
      // For anonymous or EU Login users this page doesn't exist.
      if ($this->userHasUnlimitedAccess()) {
        throw new NotFoundHttpException();
      }
      $event->getResponse()->setStatusCode(Response::HTTP_FORBIDDEN);
    }
  }

  /**
   * Limits the access to the site functionality for one-time-login sessions.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The request event.
   */
  public function redirectWhenNoAccess(GetResponseEvent $event): void {
    if ($this->limitedAccessIsDisabled()) {
      return;
    }

    // Allow anonymous or EU Login users.
    if ($this->userHasUnlimitedAccess()) {
      return;
    }

    $request = $event->getRequest();

    // Applicable only for humans.
    if ($request->getRequestFormat() !== 'html') {
      return;
    }

    // Don't redirect on POST requests.
    if (!$request->isMethodSafe()) {
      return;
    }

    // Check if the current route is allowed.
    if ($this->isRouteAllowed()) {
      return;
    }

    // The browser caches the redirect. Make sure the cache is not leaking
    // between user with different EU Login link status.
    $cache_metadata = (new CacheableMetadata())->addCacheContexts(['user.is_eulogin']);
    // Redirect to 'Limited access' page.
    $response = new LocalRedirectResponse(Url::fromRoute('joinup_eulogin.page.limited_access')->toString());
    $event->setResponse($response->addCacheableDependency($cache_metadata));
  }

  /**
   * Checks if the current route is allowed.
   *
   * @return bool
   *   If the current route is excluded.
   */
  protected function isRouteAllowed(): bool {
    // Un-routed?
    if (!$route_name = $this->routeMatch->getRouteName()) {
      return TRUE;
    }

    $allowed_routes = [
      // 'Page not found' is always accessible.
      'joinup_core.not_found',
      // The 'Limited access' warning page.
      'joinup_eulogin.page.limited_access',
      // CSRF token route.
      'system.csrftoken',
      // Still able to contact support.
      'contact_form.contact_page',
      // Can see their account page.
      'user.page',
      // Able to log out.
      'user.logout',
      // A Well-Known URL for Changing Passwords.
      // @see https://wicg.github.io/change-password-url
      'user.well-known.change_password',
    ];

    // Check first routes without parameters.
    if (in_array($route_name, $allowed_routes, TRUE)) {
      return TRUE;
    }

    // Allowed if we're on the current user canonical page or edit form.
    if (in_array($route_name, ['entity.user.canonical', 'entity.user.edit_form'], TRUE) && $this->routeMatch->getRawParameter('user') === $this->currentUser->id()) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Checks if the current user has unlimited access.
   *
   * @return bool
   *   TRUE if the current user has unlimited access.
   */
  protected function userHasUnlimitedAccess(): bool {
    // A user has unlimited access if:
    // - is anonymous.
    return $this->currentUser->isAnonymous()
    // - or is an EU Login linked user.
    || $this->authmap->get($this->currentUser->id(), 'cas');
  }

  /**
   * Checks if limiting access is disabled.
   *
   * @return bool
   *   If limiting access is disabled.
   */
  protected function limitedAccessIsDisabled(): bool {
    return $this->state->get('joinup_eulogin.disable_limited_access', FALSE);
  }

}
