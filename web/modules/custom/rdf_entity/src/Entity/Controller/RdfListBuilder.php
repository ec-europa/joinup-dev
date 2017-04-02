<?php

namespace Drupal\rdf_entity\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\rdf_entity\Form\RdfListBuilderFilterForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for rdf_entity entity.
 */
class RdfListBuilder extends EntityListBuilder {

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * Constructs a new NodeListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, RedirectDestinationInterface $redirect_destination) {
    parent::__construct($entity_type, $storage);
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('redirect.destination')
    );
  }

  /**
   * The pager size.
   *
   * @var int
   */
  protected $limit = 20;

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $request = \Drupal::request();
    $rdf_storage = $this->getStorage();
    /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info */
    $bundle_info = \Drupal::service('entity_type.bundle.info');
    /** @var \Drupal\rdf_entity\Entity\Query\Sparql\Query $query */
    $query = $rdf_storage->getQuery();

    // If a graph type is set in the url, validate it, and use it in the query.
    $graph = $request->get('graph');
    if (!empty($graph)) {
      $definitions = $rdf_storage->getGraphDefinitions();
      if (isset($definitions[$graph])) {
        // Use the graph to build the list.
        $query->setGraphType([$graph]);
      }
    }
    else {
      $query->setGraphType($rdf_storage->getGraphHandler()->getEntityTypeEnabledGraphs());
    }

    if ($rid = $request->get('rid') ?: NULL) {
      $rid = in_array($rid, array_keys($bundle_info->getBundleInfo('rdf_entity'))) ? [$rid] : NULL;
    }
    $query->condition('rid', $rid, 'IN');

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    $header = $this->buildHeader();
    $query->tableSort($header);

    return $query->execute();
  }

  /**
   * {@inheritdoc}
   *
   * We override ::render() so that we can add our own content above the table.
   * parent::render() is where EntityListBuilder creates the table using our
   * buildHeader() and buildRow() implementations.
   */
  public function render() {
    return [
      'filter_form' => \Drupal::formBuilder()->getForm(RdfListBuilderFilterForm::class),
    ] + parent::render();
  }

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the Rdf list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header = [
      'id' => [
        'data' => $this->t('URI'),
        'field' => 'id',
        'specifier' => 'id',
      ],
      'rid' => [
        'data' => $this->t('Bundle'),
        'field' => 'rid',
        'specifier' => 'rid',
      ],
      'status' => [
        'data' => $this->t('Status'),
        'field' => 'status',
        'specifier' => 'status',
      ],
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\rdf_entity\Entity\Rdf */
    $row['id'] = $entity->link();
    $row['rid'] = $entity->bundle();
    $row['status'] = $entity->isPublished() ? $this->t('Published') : $this->t('Unpublished');
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    $destination = $this->redirectDestination->getAsArray();
    foreach ($operations as $key => $operation) {
      $operations[$key]['query'] = $destination;
    }
    return $operations;
  }

}
