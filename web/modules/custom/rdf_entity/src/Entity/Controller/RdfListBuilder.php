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
    $mapping = $rdf_storage->getRdfBundleList();
    if (!$mapping) {
      return [];
    }
    $query = $rdf_storage->getQuery()
      ->condition('rid', NULL, 'IN');

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
    $build['description'] = array(
      '#markup' => $this->t('The Rdf entities are stored in a triple store.'),
    );
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
    return $row + parent::buildRow($entity);
  }

}
