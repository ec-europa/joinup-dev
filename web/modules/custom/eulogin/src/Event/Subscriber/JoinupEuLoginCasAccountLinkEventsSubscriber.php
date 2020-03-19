<?php

declare(strict_types = 1);

namespace Drupal\joinup_eulogin\Event\Subscriber;

use Drupal\cas_account_link\Event\CasAccountLinkEvents;
use Drupal\cas_account_link\Event\Events\CasAccountLinkEmailCollisionEvent;
use Drupal\cas_account_link\Event\Events\CasAccountLinkPostLinkEvent;
use Drupal\cas_account_link\Event\Events\CasAccountLinkValidateEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to CAS Account Link events.
 */
class JoinupEuLoginCasAccountLinkEventsSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new event subscriber instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      CasAccountLinkEvents::EMAIL_COLLISION => 'setEmailCollisionMessage',
      CasAccountLinkEvents::POST_LINK => [
        ['setRandomPassword'],
        ['setMessageAndRedirect'],
      ],
      CasAccountLinkEvents::VALIDATE => 'preventLinkingLimitedAccessBypassAccounts',
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
   * Sets a random password on the account.
   *
   * This is an additional security measure to prevent the user's original
   * password staying behind in hashed form in the database when we no longer
   * need it.
   *
   * @param \Drupal\cas_account_link\Event\Events\CasAccountLinkPostLinkEvent $event
   *   The CAS Account Link post-linking event object.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown if the bundle does not exist or was needed but not specified.
   */
  public function setRandomPassword(CasAccountLinkPostLinkEvent $event): void {
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

  /**
   * Disallow linking accounts that can bypass the limited access.
   *
   * The only users that can bypass the access limitation are accounts used for
   * maintenance and demonstration purposes, such as UID 1, demo users, and
   * other functional accounts. It does not make sense to link these accounts
   * with EU Login since that service is intended to identify actual human EU
   * citizens.
   *
   * @param \Drupal\cas_account_link\Event\Events\CasAccountLinkValidateEvent $event
   *   The CAS Account Link validate event object.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the 'user' entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public function preventLinkingLimitedAccessBypassAccounts(CasAccountLinkValidateEvent $event): void {
    $form_state = $event->getFormState();
    if ($form_state->getValue('account_exist') === 'no') {
      return;
    }

    /** @var \Drupal\user\UserInterface $account */
    $account = $this->entityTypeManager->getStorage('user')->load($form_state->get('uid'));

    if ($account->hasPermission('bypass limited access')) {
      $form_state->setErrorByName('login][name', $this->t('Linking the local %username user with an EU Login account is not allowed.', [
        '%username' => $account->getDisplayName(),
      ]));
    }
  }

}
