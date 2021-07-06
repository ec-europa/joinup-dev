<?php

declare(strict_types = 1);

namespace Drupal\joinup_core\Plugin\Field\FieldType;

use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\link\Plugin\Field\FieldType\LinkItem;

/**
 * Variant of the 'link' field intended for reporting inappropriate content.
 *
 * @FieldType(
 *   id = "report_link",
 *   label = @Translation("Report"),
 *   description = @Translation("A link that can be used to report inappropriate content."),
 *   default_widget = "link_default",
 *   default_formatter = "link",
 *   constraints = {
 *     "LinkType" = {},
 *     "LinkAccess" = {},
 *     "LinkExternalProtocols" = {},
 *     "LinkNotExistingInternal" = {}
 *   }
 * )
 */
class ReportLinkItem extends LinkItem {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $this->ensureComputedValue();
    $value = parent::get('uri')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $this->ensureComputedValue();
    return $this->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function set($property_name, $value, $notify = TRUE) {
    // There is a conflict between
    // \Drupal\Core\TypedData\Plugin\DataType\Map::set
    // and \Drupal\Core\TypedData\ComputedItemListTrait::set so we are
    // overriding the function to match the one from the Map.
    parent::set($property_name, $value);
  }

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $entity = $this->getEntity();
    if (!$entity->isNew()) {
      $url = $this->getEntity()->toUrl()->toString();
      $value = [
        'uri' => 'internal:/contact',
        'title' => $this->t('Report abusive content'),
        'options' => [
          'query' => [
            'category' => 'report',
            'uri' => $url,
            'destination' => $url,
          ],
        ],
      ];
      $this->setValue($value);
    }
  }

}
