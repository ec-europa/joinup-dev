<?php

namespace Drupal\rdf_entity\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\rdf_entity\Form\RdfListBuilderFilterForm;

/**
 * Provides a list controller for rdf_entity entity.
 */
class RdfListBuilder extends EntityListBuilder {

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
    $query = \Drupal::entityQuery('rdf_entity');

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
    // Special treatment for 'solution' and 'asset_release'.
    // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3126
    if ($rid[0] === 'asset_release') {
      $query->exists('field_isr_is_version_of');
    }
    elseif ($rid[0] === 'solution') {
      $query->notExists('field_isr_is_version_of');
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
