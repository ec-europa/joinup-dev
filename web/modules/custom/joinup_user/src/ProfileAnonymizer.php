<?php

declare(strict_types = 1);

namespace Drupal\joinup_user;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\externalauth\AuthmapInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\user\UserDataInterface;
use Drupal\user\UserInterface;

/**
 * Default class for the 'joinup_user.profile_anonymizer' service.
 */
class ProfileAnonymizer implements ProfileAnonymizerInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The authmap service.
   *
   * @var \Drupal\externalauth\AuthmapInterface
   */
  protected $authmap;

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
   * Constructs a new service instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\externalauth\AuthmapInterface $authmap
   *   The authmap service.
   * @param \Drupal\og\MembershipManagerInterface $membership_manager
   *   The OG membership manager service.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AuthmapInterface $authmap, MembershipManagerInterface $membership_manager, UserDataInterface $user_data) {
    $this->entityTypeManager = $entity_type_manager;
    $this->authmap = $authmap;
    $this->membershipManager = $membership_manager;
    $this->userData = $user_data;
  }

  /**
   * {@inheritdoc}
   */
  public function anonymize(UserInterface $account): void {
    // Delete the user photo, if exists.
    if ($photo = $account->get('field_user_photo')->entity) {
      $photo->delete();
    }

    // Delete the CAS linkage.
    $this->authmap->delete($account->id(), 'cas');

    // Delete all group memberships.
    $memberships = $this->membershipManager->getMemberships($account->id(), OgMembershipInterface::ALL_STATES);
    $membership_storage = $this->entityTypeManager->getStorage('og_membership');
    $membership_storage->delete($memberships);

    // Clear any user data.
    $this->userData->delete(NULL, $account->id());

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
      ->set('field_user_professional_domain', NULL)
      ->save();
  }

}
