<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\EventSubscriber;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\cas_account_link\Event\CasAccountLinkEvents;
use Drupal\cas_account_link\Event\Events\CasAccountLinkPostLinkEvent;
use Drupal\externalauth\Event\ExternalAuthEvents;
use Drupal\externalauth\Event\ExternalAuthLoginEvent;
use Drupal\joinup_group\Entity\GroupInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Event subscriber listening to events related to joining groups.
 *
 * When an anonymous user wants to join a group, they are redirected to EU Login
 * and a cookie is set to keep track of the group the user wants to join. This
 * event subscriber listens to login events, checks for the presence of the
 * cookie, and redirects the user back to the group they wanted to join so they
 * can decide whether they want to receive the group's notifications.
 */
class JoinGroupSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The time keeping service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new event subscriber instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time keeping service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TimeInterface $time, RequestStack $request_stack, MessengerInterface $messenger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->time = $time;
    $this->requestStack = $request_stack;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      ExternalAuthEvents::LOGIN => 'onLogin',
      CasAccountLinkEvents::POST_LINK => [
        ['onPostLink', 100],
      ],
    ];
  }

  /**
   * Listens to the ExternalAuthEvents::LOGIN event.
   *
   * When a user is logged in through EU login and has previously expressed
   * their desire to join a group by setting a cookie, create their membership
   * and redirect them to the group page.
   *
   * @param \Drupal\externalauth\Event\ExternalAuthLoginEvent $event
   *   The event firing when a user logs in through EU login.
   */
  public function onLogin(ExternalAuthLoginEvent $event): void {
    if ($group = $this->getGroup()) {
      try {
        // Subscribe the user to the group.
        $membership = $group->createMembership((int) $event->getAccount()->id());
        $this->messenger->addStatus($group->getNewMembershipSuccessMessage($membership));

        // Redirect to the group page so the user has the opportunity to
        // subscribe to group notifications.
        $url = $group->toUrl();
        if ($url instanceof Url) {
          $this->requestStack->getCurrentRequest()->query->set('destination', $url->toString());
        }
      }
      catch (\Exception $e) {
        // We are acting on user supplied data in a cookie. Do not allow any
        // exception to bubble up since this could prevent the user from logging
        // in until they manually clear their cookies. Instead delete the cookie
        // and proceed as if no cookie was set.
        $this->deleteCookie();
        return;
      }
    }
  }

  /**
   * Listens to the CasAccountLinkEvents::POST_LINK event.
   *
   * If a valid cookie is set indicating that the user is logging in with the
   * intention of joining a group, prevent the user from being redirected
   * elsewhere.
   *
   * @param \Drupal\cas_account_link\Event\Events\CasAccountLinkPostLinkEvent $event
   *   The event firing when a CAS account is linked to a Drupal account.
   */
  public function onPostLink(CasAccountLinkPostLinkEvent $event): void {
    if ($this->getGroup()) {
      // Ensure our redirection takes precedence over any other listeners. The
      // POST_LINK event fires after the LOGIN event and would redirect the user
      // to their profile page. This would overwrite our redirection.
      // @see ::onLogin()
      // @see \Drupal\joinup_eulogin\Event\Subscriber\JoinupEuLoginCasAccountLinkEventsSubscriber::setMessageAndRedirect()
      $event->stopPropagation();
    }
  }

  /**
   * Returns the group that is referenced in the cookie.
   *
   * If the user who is logging in has previously indicated that they want to
   * join a group, a cookie is set which tracks which group they want to join.
   *
   * If a cookie is set and turns out to contain invalid data, the cookie is
   * deleted because it is possibly being tampered with.
   *
   * @return \Drupal\joinup_group\Entity\GroupInterface|null
   *   The group that is referenced in the cookie, or NULL in case no cookie is
   *   set, or the cookie contains invalid data.
   */
  protected function getGroup(): ?GroupInterface {
    if (!empty($entity_id = $this->requestStack->getCurrentRequest()->cookies->get('join_group'))) {
      try {
        $entity = $this->entityTypeManager->getStorage('rdf_entity')->load($entity_id);
        if ($entity instanceof GroupInterface) {
          return $entity;
        }
      }
      catch (\Exception $e) {
      }
      // We are acting on user supplied data in a cookie. If the group cannot be
      // loaded, or an exception occurs, possibly the cookie was tampered with.
      // Do not allow any exceptions to bubble up since they could prevent the
      // user from logging in until they manually clear their cookies. Instead
      // delete the cookie and carry on as if no cookie was set.
      $this->deleteCookie();
    }
    return NULL;
  }

  /**
   * Deletes the cookie which tracks the group an anonymous user wants to join.
   */
  protected function deleteCookie(): void {
    setcookie('join_group', '', $this->time->getRequestTime() - 86400, '/');
  }

  /**
   * Returns the canonical URL of the group that is referenced in the cookie.
   *
   * If the user who is logging in has previously indicated that they want to
   * join a group, a cookie is set which tracks which group they want to join.
   *
   * If a cookie is set and turns out to contain invalid data, the cookie is
   * deleted because it is possibly being tampered with.
   *
   * @return \Drupal\Core\Url|null
   *   The URL of the group that is referenced in the cookie, or NULL in case no
   *   cookie is set, or the cookie contains invalid data.
   */
  protected function getGroupUrl(): ?Url {
    if (!empty($entity_id = $this->requestStack->getCurrentRequest()->cookies->get('join_group'))) {
      try {
        $entity = $this->entityTypeManager->getStorage('rdf_entity')->load($entity_id);
        if ($entity instanceof GroupInterface) {
          $url = $entity->toUrl();
          if ($url instanceof Url) {
            return $url;
          }
        }
      }
      catch (\Exception $e) {
      }
      // We are acting on user supplied data in a cookie. If the group does not
      // exist or an exception occurs during the loading of the entity or
      // generation of the URL, then possibly the cookie was tampered with.
      // Delete the cookie and carry on as if no cookie was set.
      $this->deleteCookie();
    }
    return NULL;
  }

}
