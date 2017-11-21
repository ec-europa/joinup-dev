<?php

namespace Drupal\solution\EventSubscriber;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\og\Entity\OgMembership;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Alters the redirect to 'solution.transfer_ownership_confirm' route.
 *
 * In case the action has registered errors, we cancel the redirection to the
 * confirmation form. Instead, we redirect back to the main view and we show
 * some warnings, explaining the errors.
 */
class TransferSolutionOwnershipSubscriber implements EventSubscriberInterface {

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The user private tempstore.
   *
   * @var \Drupal\user\PrivateTempStore
   */
  protected $tempStore;

  /**
   * Constructs a new event subscriber.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user service.
   * @param \Drupal\user\PrivateTempStoreFactory $tempstore_factory
   *   The user private tempstore factory.
   */
  public function __construct(AccountInterface $current_user, PrivateTempStoreFactory $tempstore_factory) {
    $this->currentUser = $current_user;
    $this->tempStore = $tempstore_factory->get('joinup_transfer_solution_ownership');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [KernelEvents::RESPONSE => ['alterRedirection']];
  }

  /**
   * Alters the redirection to 'solution.transfer_ownership_confirm', if case.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The response event.
   */
  public function alterRedirection(FilterResponseEvent $event) {
    $response = $event->getResponse();
    if ($response->isRedirect() && $response instanceof RedirectResponse) {
      $path = trim(parse_url($response->getTargetUrl(), PHP_URL_PATH), '/');
      /** @var \Symfony\Component\HttpFoundation\RedirectResponse $response */
      $url = Url::fromUri("internal:/$path");
      if ($url->isRouted() && ($url->getRouteName() === 'solution.transfer_ownership_confirm')) {
        $data = $this->tempStore->get($this->currentUser->id());
        // If warning or error messages were recorded, we alter the redirect to
        // reload the view but we want to show the messages to the user.
        if (!empty($data['messages'])) {
          foreach ($data['messages'] as $severity => $messages) {
            foreach ($messages as $message) {
              drupal_set_message($message, $severity);
            }
          }
          $membership = OgMembership::load($data['membership']);
          $view_url = Url::fromRoute('entity.rdf_entity.member_overview', ['rdf_entity' => $membership->getGroupId()]);
          $response->setTargetUrl($view_url->toString());
          // We go back to the view, expecting a new user selection, so we clear
          // the old user input.
          $this->tempStore->delete($this->currentUser->id());
        }
      }
    }
  }

}
