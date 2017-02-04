<?php

namespace Drupal\rdf_entity\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\rdf_entity\Form\RdfListBuilderFilterForm;

/**
 * Provides a list controller for rdf_entity entity.
 *
 * @ingroup content_entity_example
 */
class RdfListBuilder extends EntityListBuilder {
  protected $limit = 20;

  /**
   * {@inheritdoc}
   */
  public function load() {
    /** @var \Drupal\rdf_entity\Entity\RdfEntitySparqlStorage $rdf_storage */
    $rdf_storage = $this->getStorage();
    /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info */
    $bundle_info = \Drupal::service('entity_type.bundle.info');
    $request = \Drupal::request();
    $rid = $request->get('rid') ?: NULL;
    if ($rid) {
      $rid = in_array($rid, array_keys($bundle_info->getBundleInfo('rdf_entity'))) ? [$rid] : NULL;
    }

    $query = $rdf_storage->getQuery()->condition('rid', $rid, 'IN');

    // If a graph type is set in the url, validate it, and use it in the query.
    $graph = $request->get('graph');
    if (!empty($graph)) {
      $def = $rdf_storage->getGraphDefinitions();
      if (isset($def[$graph])) {
        // Use the graph to build the list.
        $query->setGraphType([$graph]);
      }
    }
    else {
      $query->setGraphType($rdf_storage->getGraphHandler()->getEntityTypeEnabledGraphs());
    }

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    $header = $this->buildHeader();
    $query->tableSort($header);
    $rids = $query->execute();
    return $this->storage->loadMultiple($rids);
  }

  /**
   * {@inheritdoc}
   *
   * We override ::render() so that we can add our own content above the table.
   * parent::render() is where EntityListBuilder creates the table using our
   * buildHeader() and buildRow() implementations.
   */
  public function render() {
    /** @var RdfEntitySparqlStorage $storage */
    $storage = $this->storage;
    $definitions = $storage->getGraphDefinitions();
    if (count($definitions)) {
      $options = [];
      foreach ($definitions as $name => $definition) {
        $options[$name] = $definition['title'];
      }
      // Embed the graph selection form.
      $form = \Drupal::formBuilder()->getForm(RdfListBuilderFilterForm::class, $options);
      if ($form) {
        $build['graph_form'] = $form;
      }
    }
    $build['table'] = parent::render();
    return $build;
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
    $header = array(
      'id' => array(
        'data' => $this->t('URI'),
        'field' => 'id',
        'specifier' => 'id',
      ),
      'rid' => array(
        'data' => $this->t('Bundle'),
        'field' => 'rid',
        'specifier' => 'rid',
      ),
      'status' => array(
        'data' => $this->t('Status'),
        'field' => 'status',
        'specifier' => 'status',
      ),
    );
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

}
