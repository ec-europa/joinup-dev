<?php

declare(strict_types = 1);

namespace Drupal\asset_distribution\Plugin\search_api\processor;

use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;

/**
 * Indexes the distribution data into the parent solution or release fields.
 *
 * Distributions are not shown in the site-wide search results. Instead, their
 * data (title, description, URL, licence, etc) is indexed in the parent Search
 * API record, so that searching for a keyword belonging to a distribution will
 * retrieve their parent (release or solution).
 *
 * @SearchApiProcessor(
 *   id = "index_distribution_data",
 *   label = @Translation("Index distribution data"),
 *   description = @Translation("Indexes the distribution data into the distribution parent Search API record."),
 *   stages = {
 *     "preprocess_index" = 0,
 *   },
 * )
 */
class IndexDistributionData extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index): bool {
    foreach ($index->getDatasources() as $datasource) {
      if ($datasource->getEntityTypeId() == 'rdf_entity') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array $items): void {
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item) {
      // Append data from child distributions.
      $this->appendDistributionData($item);
    }
  }

  /**
   * Appends the distribution data to the parent's Search API description field.
   *
   * @param \Drupal\search_api\Item\ItemInterface $item
   *   The Search API item.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown when $item->getOriginalObject() parameter, $load, is TRUE but the
   *   object could not be loaded.
   */
  protected function appendDistributionData(ItemInterface $item): void {
    /** @var \Drupal\rdf_entity\RdfInterface $entity */
    $entity = $item->getOriginalObject()->getValue();

    // Only solutions and releases should receive distribution data.
    if (!in_array($entity->bundle(), ['asset_release', 'solution'])) {
      return;
    }

    $distribution_field_name = $entity->bundle() === 'asset_release' ? 'field_isr_distribution' : 'field_is_distribution';
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $distribution_reference */
    $distribution_reference = $entity->get($distribution_field_name);

    if ($distribution_reference->isEmpty()) {
      return;
    }

    // Get the parent Search API field.
    $sapi_field_name = $entity->bundle() === 'asset_release' ? 'field_isr_description' : 'field_is_description';
    $sapi_field = $item->getField($sapi_field_name);

    // Iterate over all distributions and append their data to Search API field.
    /** @var \Drupal\rdf_entity\RdfInterface $distribution */
    foreach ($distribution_reference->referencedEntities() as $distribution) {
      // Add the distribution title.
      $sapi_field->addValue($distribution->label());

      // Add the distribution description.
      $distribution_field_item_list = $distribution->get('field_ad_description');
      if (!$distribution_field_item_list->isEmpty()) {
        $sapi_field->addValue(check_markup($distribution_field_item_list->value, $distribution_field_item_list->format));
      }

      // Add the distribution URL.
      /** @var \Drupal\file_url\Plugin\Field\FieldType\FileUrlFieldItemList $distribution_field_item_list */
      $distribution_field_item_list = $distribution->get('field_ad_access_url');
      if (!$distribution_field_item_list->isEmpty()) {
        foreach ($distribution_field_item_list->referencedEntities() as $file) {
          $sapi_field->addValue(file_create_url($file->getFileUri()));
        }
      }

      // Add the distribution licence.
      $distribution_field_item_list = $distribution->get('field_ad_licence');
      if (!$distribution_field_item_list->isEmpty()) {
        foreach ($distribution_field_item_list->referencedEntities() as $licence) {
          $sapi_field->addValue($licence->label());
        }
      }

      // Add the distribution format.
      $distribution_field_item_list = $distribution->get('field_ad_format');
      if (!$distribution_field_item_list->isEmpty()) {
        foreach ($distribution_field_item_list->referencedEntities() as $format) {
          $sapi_field->addValue($format->label());
        }
      }

      // Add the distribution representation technique.
      $distribution_field_item_list = $distribution->get('field_ad_repr_technique');
      if (!$distribution_field_item_list->isEmpty()) {
        foreach ($distribution_field_item_list->referencedEntities() as $technique) {
          $sapi_field->addValue($technique->label());
        }
      }
    }
  }

}
