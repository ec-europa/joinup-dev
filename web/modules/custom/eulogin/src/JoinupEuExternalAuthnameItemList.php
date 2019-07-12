<?php

declare(strict_types = 1);

namespace Drupal\joinup_eulogin;

use Drupal\Core\Field\FieldItemList;
use Drupal\user\Entity\User;

/**
 * Computed field that returns the external authname of a user.
 */
class JoinupEuExternalAuthnameItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  protected function createItem($offset = 0, $value = NULL) {
    $entity = $this->getEntity();

    if (!$entity instanceof User) {
      throw new \Exception('This class can be used only with the user entity.');
    }

    $value = empty($entity->id()) ? NULL : \Drupal::service('cas.user_manager')->getCasUsernameForAccount($entity->id());
    return parent::createItem($offset, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function getValue($include_computed = FALSE) {
    // Ensure that there is always one item created.
    $this->ensureLoaded();
    return parent::getValue($include_computed);
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    $this->ensureLoaded();
    return new \ArrayIterator($this->list);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $this->ensureLoaded();
    return parent::isEmpty();
  }

  /**
   * Makes sure that the item list is never empty.
   *
   * For 'normal' fields that use database storage the field item list is
   * initially empty, but since this is a computed field this always has a
   * value.
   * Make sure the item list is always populated, so this field is not skipped
   * for rendering in EntityViewDisplay and friends.
   *
   * This trick has been borrowed from issue #2846554 which does the same for
   * the PathItem field.
   *
   * @see https://www.drupal.org/node/2846554
   */
  protected function ensureLoaded() {
    if (!isset($this->list[0])) {
      $this->list[0] = $this->createItem(0);
    }
  }

}
