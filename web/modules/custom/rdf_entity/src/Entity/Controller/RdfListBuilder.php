<?php

namespace Drupal\rdf_entity\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

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

    $query = $rdf_storage->getQuery()
      ->condition('rid', NULL, 'IN');
    // If a graph type is set in the url, validate it, and use it in the query.
    if (!empty($_GET['graph'])) {
      $def = $rdf_storage->getGraphsDefinition();
      if (is_string($_GET['graph']) && isset($def[$_GET['graph']])) {
        // Use the graph to build the list.
        $query->setGraphType([$_GET['graph']]);
        // Use the graph to do the 'load multiple'.
        $this->storage->setActiveGraphType([$_GET['graph']]);
      }
    }
    else {
      if (\Drupal::moduleHandler()->moduleExists('rdf_draft')) {
        $this->storage->setActiveGraphType(['default', 'draft']);
      }
      else {
        $this->storage->setActiveGraphType(['default']);
      }
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
    $definitions = $storage->getGraphsDefinition();
    if (count($definitions)) {
      $options = [];
      foreach ($definitions as $name => $definition) {
        $options[$name] = $definition['title'];
      }
      // Embed the graph selection form.
      $form = \Drupal::formBuilder()->getForm('Drupal\rdf_entity\Form\GraphSelectForm', $options);
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
