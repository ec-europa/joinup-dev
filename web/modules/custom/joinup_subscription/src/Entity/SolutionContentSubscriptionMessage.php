<?php

declare(strict_types = 1);

namespace Drupal\joinup_subscription\Entity;

use Drupal\joinup_bundle_class\JoinupBundleClassFieldAccessTrait;
use Drupal\joinup_group\Entity\GroupContentInterface;
use Drupal\message\Entity\Message;

/**
 * A solution content subscription message.
 */
class SolutionContentSubscriptionMessage extends Message implements SolutionContentSubscriptionMessageInterface {

  use JoinupBundleClassFieldAccessTrait;

  /**
   * {@inheritdoc}
   */
  public function getSubscribedGroupContent(): ?GroupContentInterface {
    $referenced_entity = $this->getFirstReferencedEntity('field_solution_content');
    if ($referenced_entity instanceof GroupContentInterface) {
      return $referenced_entity;
    }

    return NULL;
  }

}
