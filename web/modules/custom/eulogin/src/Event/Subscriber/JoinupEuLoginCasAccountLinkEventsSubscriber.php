<?php

declare(strict_types = 1);

namespace Drupal\joinup_eulogin\Event\Subscriber;

use Drupal\cas_account_link\Event\CasAccountLinkEvents;
use Drupal\cas_account_link\Event\Events\CasAccountLinkPostLinkEvent;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to CAS Account Link events.
 */
class JoinupEuLoginCasAccountLinkEventsSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      CasAccountLinkEvents::POST_LINK => 'setRedirects',
    ];
  }

  /**
   * Sets the success status message and the redirect after account linking.
   *
   * @param \Drupal\cas_account_link\Event\Events\CasAccountLinkPostLinkEvent $event
   *   The CAS Account Link post-linking event object.
   */
  public function setRedirects(CasAccountLinkPostLinkEvent $event): void {
    if ($event->hasLocalAccount()) {
      /** @var \Drupal\cas\CasPropertyBag $property_bag */
      $property_bag = $event->getCasContext()['property_bag'];
      $event->setSuccessMessage($this->t('Your EU Login account %authname has been successfully linked to your local account %account.', [
        '%authname' => $property_bag->getOriginalUsername(),
        '%account' => $event->getAccount()->getDisplayName(),
      ]));
    }
    else {
      $event->setSuccessMessage($this->t('Fill in the fields below to let the Joinup community learn more about you!'));
      $event->setRedirectUrl($event->getAccount()->toUrl('edit-form'));
    }
  }

}
