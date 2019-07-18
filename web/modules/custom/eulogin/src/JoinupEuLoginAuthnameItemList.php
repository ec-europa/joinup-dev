<?php

declare(strict_types = 1);

namespace Drupal\joinup_eulogin;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\user\UserInterface;

/**
 * Computed field that returns the external authname of a user.
 */
class JoinupEuLoginAuthnameItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $entity = $this->getEntity();

    if (!$entity instanceof UserInterface) {
      throw new \Exception('This class can be used only with the user entity.');
    }

    $value = empty($entity->id()) ? NULL : \Drupal::service('cas.user_manager')->getCasUsernameForAccount($entity->id());
    $this->list[0] = $this->createItem(0, $value);
  }

}
