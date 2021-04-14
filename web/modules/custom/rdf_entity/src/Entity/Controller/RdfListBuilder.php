<?php

declare(strict_types = 1);

namespace Drupal\rdf_entity\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\rdf_entity\Form\RdfListBuilderFilterForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

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
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * Constructs a new NodeListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   * @param \Symfony\Component\HttpFoundation\Request $current_request
   *   The current request.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, RedirectDestinationInterface $redirect_destination, Request $current_request, EntityTypeBundleInfoInterface $bundle_info, FormBuilderInterface $form_builder) {
    parent::__construct($entity_type, $storage);
    $this->redirectDestination = $redirect_destination;
    $this->currentRequest = $current_request;
    $this->bundleInfo = $bundle_info;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('redirect.destination'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('entity_type.bundle.info'),
      $container->get('form_builder')
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
    /** @var \Drupal\sparql_entity_storage\SparqlEntityStorageInterface $sparql_storage */
    $sparql_storage = $this->getStorage();
    /** @var \Drupal\sparql_entity_storage\Entity\Query\Sparql\Query $query */
    $query = $sparql_storage->getQuery();

    // If a graph type is set in the url, validate it, and use it in the query.
    $graph = $this->currentRequest->get('graph');
    if (!empty($graph)) {
      $definitions = $sparql_storage->getGraphDefinitions();
      if (isset($definitions[$graph])) {
        // Use the graph to build the list.
        $query->graphs([$graph]);
      }
    }

    // If an RDF bundle is specified in the URL, use it in the query if it
    // exists.
    if ($rid = $this->currentRequest->get('rid') ?: NULL) {
      $rid = in_array($rid, array_keys($this->bundleInfo->getBundleInfo('rdf_entity'))) ? [$rid] : NULL;
      $query->condition('rid', $rid, 'IN');
    }

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
      'filter_form' => $this->formBuilder->getForm(RdfListBuilderFilterForm::class),
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
    /** @var \Drupal\rdf_entity\RdfInterface $entity */
    $row['id'] = $entity->toLink();
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
