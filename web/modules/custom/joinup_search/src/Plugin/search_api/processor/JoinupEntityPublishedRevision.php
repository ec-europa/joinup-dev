<?php

namespace Drupal\joinup_search\Plugin\search_api\processor;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\rdf_entity\RdfInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\state_machine_revisions\RevisionManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Ensures that the item to be indexed is the published version.
 *
 * @SearchApiProcessor(
 *   id = "joinup_entity_published_revision",
 *   label = @Translation("Joinup entity published revision"),
 *   description = @Translation("Ensures that the version of the entity is the published revision."),
 *   stages = {
 *     "alter_items" = -20,
 *   },
 * )
 */
class JoinupEntityPublishedRevision extends ProcessorPluginBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    $supported_entity_types = ['node', 'rdf_entity'];
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
  public function alterIndexedItems(array &$items) {
    /** @var \Drupal\search_api\Item\Item[] $items */
    foreach ($items as $item_id => $item) {
      $original_object = $item->getOriginalObject();
      $object = $original_object->getValue();
      if ($object instanceof NodeInterface) {
        if (!$object->isPublished()) {
          $published = $this->entityTypeManager->getStorage('node')->load($object->id());
          if (!empty($published) && $published->isPublished()) {
            $original_object->setValue($published);
            $item->setOriginalObject($original_object);
            $items[$item_id] = $item;
          }
        }
      }
      elseif ($object instanceof RdfInterface) {
        if (!$object->isPublished()) {
          /** @var \Drupal\rdf_entity\Entity\RdfEntitySparqlStorage $rdf_storage */
          $rdf_storage = $this->entityTypeManager->getStorage('rdf_entity');
          $rdf_storage->setRequestGraphs($object->id(), ['default']);
          $published = $rdf_storage->load($object->id());
          $rdf_storage->getGraphHandler()->resetRequestGraphs([$object->id()]);
          if (!empty($published)) {
            $original_object->setValue($published);
            $item->setOriginalObject($original_object);
            $items[$item_id] = $item;
          }
        }
      }
    }
  }

}
