<?php

declare(strict_types = 1);

namespace Drupal\joinup_user\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Formatter for the user tristate status field.
 *
 * @FieldFormatter(
 *   id = "joinp_user_account_status",
 *   label = @Translation("Account status (tristate)"),
 *   description = @Translation("Shows a human-redable label for the user account tristate status."),
 *   field_types = {
 *     "integer",
 *   },
 * )
 */
class JoinupAccountStatusFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    /** @var \Drupal\joinup_user\Entity\JoinupUserInterface $account */
    $account = $items->getEntity();

    if ($account->getEntityTypeId() !== 'user' || $items->getName() !== 'status') {
      throw new \Exception("The 'joinp_user_account_status' formatter should be used only on the user 'status' field");
    }

    $labels = [
      1 => $this->t('Active'),
      0 => $this->t('Blocked'),
      -1 => $this->t('Cancelled'),
    ];

    $elements = [];
    foreach ($items as $delta => $item) {
      $elements[$delta] = ['#markup' => $labels[$item->value]];
    }

    // Add user account as cache dependency.
    (new CacheableMetadata())
      ->addCacheableDependency($account)
      ->applyTo($elements);

    return $elements;
  }

}
