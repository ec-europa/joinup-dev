<?php

namespace Drupal\rdf_file\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;

/**
 * Plugin implementation of the 'file_default' formatter.
 *
 * @FieldFormatter(
 *   id = "rdf_file_default",
 *   label = @Translation("Generic file"),
 *   field_types = {
 *     "rdf_file"
 *   }
 * )
 */
class GenericRdfFileFormatter extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $item = $file->_referringItem;
      $elements[$delta] = array(
        '#theme' => 'file_link',
        '#file' => $file,
        '#description' => $item->description,
        '#cache' => array(
          'tags' => $file->getCacheTags(),
        ),
      );
      // Pass field item attributes to the theme function.
      if (isset($item->_attributes)) {
        $elements[$delta] += array('#attributes' => array());
        $elements[$delta]['#attributes'] += $item->_attributes;
        // Unset field item attributes since they have been included in the
        // formatter output and should not be rendered in the field template.
        unset($item->_attributes);
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   *
   * Loads the entities referenced in that field across all the entities being
   * viewed.
   */
  public function prepareView(array $entities_items) {
    /** @var \Drupal\rdf_file\RdfFileHandler $file_handler */
    $file_handler = \Drupal::service('rdf_file.handler');
    // Collect entity IDs to load. For performance, we want to use a single
    // "multiple entity load" to load all the entities for the multiple
    // "entity reference item lists" being displayed. We thus cannot use
    // \Drupal\Core\Field\EntityReferenceFieldItemList::referencedEntities().
    $ids = array();
    foreach ($entities_items as $items) {
      foreach ($items as $item) {
        // To avoid trying to reload non-existent entities in
        // getEntitiesToView(), explicitly mark the items where $item->entity
        // contains a valid entity ready for display. All items are initialized
        // at FALSE.
        $item->_loaded = FALSE;
        if ($this->needsEntityLoad($item)) {
          $file = $file_handler->UrlToFile($item->target_id);
          $entities[$item->target_id] = $file;
        }
      }
    }

    // For each item, pre-populate the loaded entity in $item->entity, and set
    // the 'loaded' flag.
    foreach ($entities_items as $items) {
      foreach ($items as $item) {
        if (isset($entities[$item->target_id])) {
          $item->entity = $entities[$item->target_id];
          $item->_loaded = TRUE;
        }
        elseif ($item->hasNewEntity()) {
          $item->_loaded = TRUE;
        }
      }
    }
  }

}
