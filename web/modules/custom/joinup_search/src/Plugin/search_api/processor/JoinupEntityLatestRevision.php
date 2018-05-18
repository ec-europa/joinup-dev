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
 * Ensures that the item to be indexed is the latest version.
 *
 * @SearchApiProcessor(
 *   id = "joinup_entity_latest_revision",
 *   label = @Translation("Joinup entity latest revision"),
 *   description = @Translation("Ensures that the version of the entity is the latest revision."),
 *   stages = {
 *     "alter_items" = -20,
 *   },
 * )
 */
class JoinupEntityLatestRevision extends ProcessorPluginBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The state machine revisions manager service.
   *
   * @var \Drupal\state_machine_revisions\RevisionManagerInterface
   */
  protected $revisionManager;

  /**
   * Constructs a JoinupEntityLatestRevision object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\state_machine_revisions\RevisionManagerInterface $revision_manager
   *   The state machine revisions manager service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager, RevisionManagerInterface $revision_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->revisionManager = $revision_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('state_machine_revisions.revision_manager')
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
        if (!$this->revisionManager->isLatestRevision($object)) {
          $latest = $this->revisionManager->loadLatestRevision($object);
          if (!empty($latest)) {
            $original_object->setValue($latest);
            $item->setOriginalObject($original_object);
            $items[$item_id] = $item;
          }
        }
      }
      elseif ($object instanceof RdfInterface) {
        if ($object->isPublished()) {
          /** @var \Drupal\rdf_entity\RdfEntitySparqlStorageInterface $rdf_storage */
          $rdf_storage = $this->entityTypeManager->getStorage('rdf_entity');
          $latest = $rdf_storage->load($object->id(), ['draft']);
          if (!empty($latest)) {
            $original_object->setValue($latest);
            $item->setOriginalObject($original_object);
            $items[$item_id] = $item;
          }
        }
      }
    }
  }

}
