<?php

/**
 * @file
 * Contains \SubscriptionSubContext.
 */

declare(strict_types = 1);

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\EntityTrait;
use Drupal\joinup\Traits\UserTrait;
use Drupal\joinup_subscription\JoinupSubscriptionInterface;

/**
 * Behat step definitions for testing subscriptions.
 */
class SubscriptionSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use EntityTrait;
  use UserTrait;

  /**
   * Subscribes the given users to the given entity.
   *
   * @param \Behat\Gherkin\Node\TableNode $table
   *   A table with the keys 'username' and 'title'.
   * @param string $bundle
   *   The entity bundle to which the entity with given title corresponds.
   * @param string $entity_type
   *   The entity type to which the entity with given title corresponds.
   *
   * @Given (the following ):bundle :entity_type subscriptions:
   */
  public function subscribe(TableNode $table, string $bundle, string $entity_type): void {
    $subscription_service = $this->getSubscriptionService();
    $entity_type = self::translateEntityTypeAlias($entity_type);
    foreach ($table->getColumnsHash() as $values) {
      $user = $this->getUserByName($values['username']);
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $this->getEntityByLabel($entity_type, $values['title'], $bundle);
      // @todo Currently we only have subscriptions for discussions. Provide a
      //   lookup table for the discussion ID once we have more.
      $subscription_service->subscribe($user, $entity, 'subscribe_discussions');
    }
  }

  /**
   * Returns the Joinup subscription service.
   *
   * @return \Drupal\joinup_subscription\JoinupSubscriptionInterface
   *   The subscription service.
   */
  protected function getSubscriptionService(): JoinupSubscriptionInterface {
    return \Drupal::service('joinup_subscription.subscription');
  }

}
