<?php

namespace Drupal\joinup_migrate\EventSubscriber;

use Drupal\Core\Site\Settings;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Og;
use Drupal\og\OgMembershipInterface;
use Drupal\user\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A subscriber that acts after a user profile row is saved.
 */
class UserJoinupMembershipSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [MigrateEvents::POST_ROW_SAVE => 'addJoinupMembership'];
  }

  /**
   * Reacts after a user profile row was migrated.
   *
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   The event object.
   */
  public function addJoinupMembership(MigratePostRowSaveEvent $event) {
    if ($event->getMigration()->id() !== 'user_profile') {
      return;
    }

    $joinup_collection_label = Settings::get('joinup_joinup_collection', 'Joinup');
    $conditions = ['label' => $joinup_collection_label, 'rid' => 'collection'];
    $storage = \Drupal::entityTypeManager()->getStorage('rdf_entity');
    if ($storage && $collections = $storage->loadByProperties($conditions)) {
      $joinup_collection = reset($collections);
      if ($uid = $event->getRow()->getSourceProperty('uid')) {
        if ($user_account = User::load($uid)) {
          // Add the membership only it doesn't exist yet.
          if (!Og::getMembership($joinup_collection, $user_account, [])) {
            OgMembership::create()
              ->setGroup($joinup_collection)
              ->setUser($user_account)
              ->setState(OgMembershipInterface::STATE_ACTIVE)
              ->setRoles()
              ->save();
          }
        }
      }
    }
  }

}
