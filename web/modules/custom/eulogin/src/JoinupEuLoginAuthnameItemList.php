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
  protected function computeValue(): void {
    /** @var \Drupal\user\UserInterface $account */
    $account = $this->getEntity();

    if (!$account instanceof UserInterface) {
      throw new \Exception('This class can be used only with the user entity.');
    }

    if ($account->isAuthenticated()) {
      /** @var \Drupal\cas\Service\CasUserManager $cas_user_manager */
      $cas_user_manager = \Drupal::service('cas.user_manager');
      if ($authname = $cas_user_manager->getCasUsernameForAccount($account->id())) {
        $this->list[0] = $this->createItem(0, $authname);
      }
    }
  }

}
