<?php

namespace Drupal\joinup_user;

use Drupal\Core\Field\FieldItemList;
use Drupal\user\Entity\User;

/**
 * Computed field that returns the full name of a user.
 *
 * A full name is composed by the first name and the family name.
 */
class UserFullNameFieldItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  protected function createItem($offset = 0, $value = NULL) {
    $entity = $this->getEntity();

    if (!$entity instanceof User) {
      throw new \Exception('This class can be used only with the user entity.');
    }

    $first_name = $entity->get('field_user_first_name')->value;
    $family_name = $entity->get('field_user_family_name')->value;

    if (empty($first_name) || empty($family_name)) {
      $value = $entity->getDisplayName();
    }
    else {
      $value = "$first_name $family_name";
    }

    return parent::createItem($offset, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function getValue($include_computed = FALSE) {
    // Ensure that there is always one item created.
    if ($this->isEmpty()) {
      $this->list[] = $this->createItem();
    }

    return parent::getValue($include_computed);
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    // Ensure that there is always one item created.
    if ($this->isEmpty()) {
      $this->list[] = $this->createItem();
    }

    return parent::getIterator();
  }

}
