<?php

declare(strict_types = 1);

namespace Drupal\joinup_user\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\joinup_user\Event\JoinupUserCancelEvent;
use Drupal\user\Entity\User;

/**
 * Wraps the Drupal core user entity class.
 */
class JoinupUser extends User implements JoinupUserInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    return [
      'status' => BaseFieldDefinition::create('integer')
        ->setLabel(t('User status'))
        ->setDescription(t('Whether the user is active, blocked or cancelled.'))
        ->setSetting('size', 'tiny')
        ->setDefaultValue(0),
    ] + parent::baseFieldDefinitions($entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function cancel(): JoinupUserInterface {
    if ($this->id() === 1) {
      throw new \Exception('UID1 cannot be cancelled.');
    }

    /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher */
    $event_dispatcher = \Drupal::service('event_dispatcher');
    $event = new JoinupUserCancelEvent($this);
    // Let subscribers react on user cancellation.
    $event_dispatcher->dispatch('joinup_user.cancel', $event);

    return $this->set('status', -1);
  }

  /**
   * {@inheritdoc}
   */
  public function isCancelled(): bool {
    return $this->get('status')->value == -1;
  }

  /**
   * {@inheritdoc}
   */
  public function isModerator(): bool {
    return $this->hasRole('moderator');
  }

}
