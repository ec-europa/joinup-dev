<?php

declare(strict_types = 1);

namespace Drupal\joinup_search\Plugin\search_api\processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\joinup_group\JoinupGroupHelper;
use Drupal\node\NodeInterface;
use Drupal\rdf_entity\RdfInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\user\UserInterface;

/**
 * Excludes unpublished entities from the index.
 *
 * @SearchApiProcessor(
 *   id = "joinup_entity_status",
 *   label = @Translation("Joinup entity status"),
 *   description = @Translation("Exclude unpublished content, rdf entities and users."),
 *   stages = {
 *     "alter_items" = 0,
 *   },
 * )
 */
class JoinupEntityStatus extends ProcessorPluginBase implements PluginFormInterface {

  use PluginFormTrait;

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    $supported_entity_types = ['node', 'rdf_entity', 'user'];
    foreach ($index->getDatasources() as $datasource) {
      if (in_array($datasource->getEntityTypeId(), $supported_entity_types)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'inverse' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['inverse'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Inverse'),
      '#description' => $this->t('If checked, unpublished entities will be indexed instead.'),
      '#default_value' => $this->configuration['inverse'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function alterIndexedItems(array &$items) {
    foreach ($items as $item_id => $item) {
      $object = $item->getOriginalObject()->getValue();
      $inverse = $this->getConfiguration()['inverse'];
      $enabled = TRUE;
      if ($object instanceof NodeInterface) {
        $parent = JoinupGroupHelper::getGroup($object);
        // Check if empty to avoid exceptions.
        // The entity can be published only if the parent entity is published.
        if (empty($parent) || !$parent->isPublished()) {
          $enabled = FALSE;
        }
        else {
          $enabled = $object->isPublished();
        }
      }
      elseif ($object instanceof RdfInterface) {
        $enabled = $object->isPublished();
      }
      elseif ($object instanceof UserInterface) {
        $enabled = $object->isActive();
      }
      $enabled = $inverse ? !$enabled : $enabled;
      if (!$enabled) {
        unset($items[$item_id]);
      }
    }
  }

}
