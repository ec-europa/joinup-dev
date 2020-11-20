<?php

declare(strict_types = 1);

namespace Drupal\joinup_user\Event\Subscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\joinup_user\Event\JoinupUserCancelEvent;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\user\UserDataInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Reacts on user cancelling.
 */
class JoinupUserUserCancelSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The OG membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * Constructs a new event subscriber instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\og\MembershipManagerInterface $membership_manager
   *   The OG membership manager service.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MembershipManagerInterface $membership_manager, UserDataInterface $user_data) {
    $this->entityTypeManager = $entity_type_manager;
    $this->membershipManager = $membership_manager;
    $this->userData = $user_data;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'joinup_user.cancel' => 'onUserCancel',
    ];
  }

  /**
   * Removes the CAS linkage when a user is cancelled.
   *
   * @param \Drupal\joinup_user\Event\JoinupUserCancelEvent $event
   *   The user cancel event.
   */
  public function onUserCancel(JoinupUserCancelEvent $event): void {
    $account = $event->getAccount();

    // Delete the user photo, if exists.
    if ($photo = $account->get('field_user_photo')->entity) {
      $photo->delete();
    }

    // Delete all group memberships.
    $memberships = $this->membershipManager->getMemberships($account->id(), OgMembershipInterface::ALL_STATES);
    if ($memberships) {
      $membership_storage = $this->entityTypeManager->getStorage('og_membership');
      $membership_storage->delete($memberships);
    }

    // Clear any user data.
    $this->userData->delete(NULL, $account->id());

    // Anonymize the user profile.
    $account
      ->setEmail(NULL)
      ->set('init', NULL)
      ->setPassword(user_password())
      ->set('langcode', LanguageInterface::LANGCODE_NOT_SPECIFIED)
      ->set('preferred_langcode', LanguageInterface::LANGCODE_NOT_SPECIFIED)
      ->set('preferred_admin_langcode', LanguageInterface::LANGCODE_NOT_SPECIFIED)
      ->set('timezone', NULL)
      ->set('roles', NULL)
      ->set('field_social_media', NULL)
      ->set('field_user_business_title', NULL)
      ->set('field_user_content', NULL)
      ->set('field_user_frequency', NULL)
      ->set('field_user_nationality', NULL)
      ->set('field_user_organisation', NULL)
      ->set('field_user_photo', NULL)
      ->set('field_user_professional_domain', NULL);
  }

}
