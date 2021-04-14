<?php

namespace Drupal\sparql_entity_storage;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of SPARQL graph entities.
 */
class SparqlGraphListBuilder extends DraggableListBuilder {

  /**
   * The access manager service.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * The entity type repository service.
   *
   * @var \Drupal\Core\Entity\EntityTypeRepositoryInterface
   */
  protected $entityTypeRepository;

  /**
   * Constructs a new entity list builder instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The access manager service.
   * @param \Drupal\Core\Entity\EntityTypeRepositoryInterface $entity_type_repository
   *   The entity type repository service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, AccessManagerInterface $access_manager, EntityTypeRepositoryInterface $entity_type_repository) {
    parent::__construct($entity_type, $storage);
    $this->accessManager = $access_manager;
    $this->entityTypeRepository = $entity_type_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('access_manager'),
      $container->get('entity_type.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sparql_entity_storage.graph.list';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'label' => $this->t('Name'),
      'description' => [
        'data' => $this->t('Description'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'entity_types' => $this->t('Entity types'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $sparql_graph) {
    /** @var \Drupal\sparql_entity_storage\SparqlGraphInterface $sparql_graph */
    $row['label'] = $sparql_graph->label();
    $row['description'] = ['#markup' => $sparql_graph->getDescription()];

    if ($entity_types = $sparql_graph->getEntityTypeIds()) {
      $labels = implode(', ', array_intersect_key(
        $this->entityTypeRepository->getEntityTypeLabels(),
        array_flip($entity_types)
      ));
    }
    else {
      $labels = $this->t('All SPARQL storage entity types');
    }
    $row['entity_types'] = ['#markup' => $labels];

    return $row + parent::buildRow($sparql_graph);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    foreach (['enable', 'disable'] as $operation) {
      if (isset($operations[$operation])) {
        $route_name = "entity.{$this->entityTypeId}.$operation";
        $parameters = [$this->entityTypeId => $entity->id()];
        if (!$this->accessManager->checkNamedRoute($route_name, $parameters)) {
          unset($operations[$operation]);
        }
      }
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form[$this->entitiesKey]['#caption'] = $this->t('Reorder graphs to establish the graphs priority.');
    return $form;
  }

}
