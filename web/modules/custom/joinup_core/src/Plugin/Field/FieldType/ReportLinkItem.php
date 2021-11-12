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
  public function get($index) {
    // There is a conflict between
    // \Drupal\Core\TypedData\Plugin\DataType\Map::get
    // and \Drupal\Core\TypedData\ComputedItemListTrait::get so we are
    // overriding the function to match the one from the Map.
    $this->ensureComputedValue();
    return parent::get($index);
  }

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
    return parent::getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function set($property_name, $value, $notify = TRUE) {
    // There is a conflict between
    // \Drupal\Core\TypedData\Plugin\DataType\Map::set
    // and \Drupal\Core\TypedData\ComputedItemListTrait::set so we are
    // overriding the function to match the one from the Map.
    // This is needed because we are extending LinkItem which is a normal non-
    // computed field. If we were a 'real' computed field this is not needed.
    parent::set($property_name, $value);
  }

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $entity = $this->getEntity();
    if (!$entity->isNew()) {
      $system_path = $this->getEntity()->toUrl()->getInternalPath();
      $path = \Drupal::service('path_alias.manager')->getAliasByPath("/$system_path");
      $value = [
        'uri' => 'internal:/contact',
        'title' => $this->t('Report abusive content'),
        'options' => [
          'query' => [
            'category' => 'report',
            'uri' => $path,
            // Remove the leading slash.
            'destination' => substr($path, 1),
          ],
        ],
      ];
      $this->setValue($value);
    }
  }

}
