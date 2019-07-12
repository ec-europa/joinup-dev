<?php

declare(strict_types = 1);

namespace Drupal\joinup_eulogin;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\user\Entity\User;

/**
 * Computed field that returns the external authname of a user.
 */
class JoinupEuExternalAuthnameItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  public static function createInstance($definition, $name = NULL, TraversableTypedDataInterface $parent = NULL) {
    return parent::createInstance(
      $definition,
      $name,
      $parent,
      \Drupal::service('cas.user_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function createItem($offset = 0, $value = NULL) {
    $entity = $this->getEntity();

    if (!$entity instanceof User) {
      throw new \Exception('This class can be used only with the user entity.');
    }

    if (empty($entity->id())) {
      return NULL;
    }

    $cas_user_manager = \Drupal::service('cas.user_manager');
    return parent::createItem($offset, $cas_user_manager->getCasUsernameForAccount($entity->id()));
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
