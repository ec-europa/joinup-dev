<?php

declare(strict_types = 1);

namespace Drupal\joinup_publication_date\Plugin\search_api\processor;

use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\joinup_publication_date\Entity\EntityPublicationTimeInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Adds the timestamp indicating when the entity was first published.
 *
 * This works for all entities that implement EntityPublicationTimeInterface.
 *
 * @SearchApiProcessor(
 *   id = "publication_time",
 *   label = @Translation("Publication time"),
 *   description = @Translation("The timestamp indicating when the entity was first published."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class PublicationTime extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(?DatasourceInterface $datasource = NULL): array {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Publication timestamp'),
        'description' => $this->t('The timestamp indicating when the entity was first published.'),
        'type' => 'integer',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['publication_time'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item): void {
    $object = $item->getOriginalObject();
    if ($object instanceof EntityAdapter) {
      $entity = $object->getEntity();
      if ($entity instanceof EntityPublicationTimeInterface) {
        $publication_time = $entity->getPublicationTime();
        if ($publication_time) {
          $index_fields = $this->index->getFields();
          $fields = $this->getFieldsHelper()->filterForPropertyPath($index_fields, NULL, 'publication_time');
          foreach ($fields as $field) {
            $field->addValue($publication_time);
          }
        }
      }
    }
  }

}
