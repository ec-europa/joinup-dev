<?php

declare(strict_types = 1);

namespace Drupal\joinup_eulogin\Event\Subscriber;

use Drupal\cas_account_link\Event\CasAccountLinkEvents;
use Drupal\cas_account_link\Event\Events\CasAccountLinkEmailCollisionEvent;
use Drupal\cas_account_link\Event\Events\CasAccountLinkPostLinkEvent;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to CAS Account Link events.
 */
class JoinupEuLoginCasAccountLinkEventsSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      CasAccountLinkEvents::EMAIL_COLLISION => 'setEmailCollisionMessage',
      CasAccountLinkEvents::POST_LINK => [
        ['setFakePassword'],
        ['setMessageAndRedirect'],
      ],
    ];
  }

  /**
   * Sets the email collision error message.
   *
   * @param \Drupal\cas_account_link\Event\Events\CasAccountLinkEmailCollisionEvent $event
   *   The CAS Account Link post-linking event object.
   */
  public function setEmailCollisionMessage(CasAccountLinkEmailCollisionEvent $event): void {
    $event->setErrorMessage([
      [
        '#markup' => $this->t('The email address %mail is already taken.', [
          '%mail' => $event->getLocalMail(),
        ]),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ],
      [
        '#markup' => $this->t('If you are the owner of this account please select the first option, otherwise contact the <a href=":contact">Joinup support</a>.', [
          ':contact' => Url::fromRoute('contact_form.contact_page')->toString(),
        ]),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ],
    ]);
  }

  /**
   * Sets a random password on the account..
   *
   * @param \Drupal\cas_account_link\Event\Events\CasAccountLinkPostLinkEvent $event
   *   The CAS Account Link post-linking event object.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown if the bundle does not exist or was needed but not specified.
   */
  public function setFakePassword(CasAccountLinkPostLinkEvent $event): void {
    if ($event->isLocalAccountSelected()) {
      // Using the same password length as for new users.
      // @see \Drupal\cas\Service\CasUserManager::randomPassword()
      $event->getAccount()->setPassword(\user_password(30))->save();
    }
  }

  /**
   * Sets the success status message and the redirect after account linking.
   *
   * @param \Drupal\cas_account_link\Event\Events\CasAccountLinkPostLinkEvent $event
   *   The CAS Account Link post-linking event object.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   *   Thrown if the bundle does not exist or was needed but not specified.
   */
  public function setMessageAndRedirect(CasAccountLinkPostLinkEvent $event): void {
    if ($event->isLocalAccountSelected()) {
      $event->setSuccessMessage($this->t('Your EU Login account %authname has been successfully linked to your local account %account.', [
        '%authname' => $event->getCasPropertyBag()->getOriginalUsername(),
        '%account' => $event->getAccount()->getDisplayName(),
      ]));
    }
    else {
      $event->setSuccessMessage($this->t('Fill in the fields below to let the Joinup community learn more about you!'));
      $event->setRedirectUrl($event->getAccount()->toUrl('edit-form'));
    }
  }

}
