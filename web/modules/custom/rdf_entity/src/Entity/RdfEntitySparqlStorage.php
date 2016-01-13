<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\ContentEntityNullStorage.
 */

namespace Drupal\rdf_entity\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Defines a null entity storage.
 *
 * Used for content entity types that have no storage.
 */
class RdfEntitySparqlStorage extends ContentEntityStorageBase {

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids = NULL) {
    $sparql = new \EasyRdf_Sparql_Client('http://localhost:8890/sparql');
    $results = $sparql->query(
      'SELECT ?entity ?lang ?desc ' .
      'WHERE{' .
        '?entity rdf:type admssw:SoftwareProject.'.
        '?entity admssw:programmingLanguage ?lang.'.
        '?entity dct:description ?desc'.
      '} LIMIT 50'
    );
    $entities = array();

    foreach ($results as $result) {
      $values = array(
        'id' => array('x-default' => str_replace('/', '\\' ,(string) $result->entity)),
        //'id' => array('x-default' => $i),
        'name' => array('x-default' => (string) $result->lang),
        'first_name' => array('x-default' => (string) $result->desc),
        'gender' => array('x-default' => 'male'),
        'user_id' => array('x-default' => 1),
        'langcode' => array('x-default' => 'und'),
      );
      $entity = new Rdf($values, 'rdf_entity');
      $entities[] = $entity;
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadMultiple(array $ids = NULL) {
    dpm($ids,  __LINE__);
  }

  /**
   * {@inheritdoc}
   */
  public function load($id_sanitized) {
    $id = str_replace('\\', '/', $id_sanitized);
    $sparql = new \EasyRdf_Sparql_Client('http://localhost:8890/sparql');
    $results = $sparql->query(
      "SELECT ?lang ?desc " .
      'WHERE{' .
      "<$id> admssw:programmingLanguage ?lang.".
      "<$id> dct:description ?desc".
      '} LIMIT 1'
    );
    $values = NULL;
    foreach ($results as $result) {
      $values = array(
        'id' => array('x-default' => $id_sanitized),
        'name' => array('x-default' => (string) $result->lang),
        'first_name' => array('x-default' => (string) $result->desc),
        'gender' => array('x-default' => 'male'),
        'user_id' => array('x-default' => 1),
        'langcode' => array('x-default' => 'und'),
      );
    }
    if ($values) {
      return new Rdf($values, 'rdf_entity');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function loadRevision($revision_id) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteRevision($revision_id) {
  }

  /**
   * {@inheritdoc}
   */
  public function loadByProperties(array $values = array()) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $entities) {
  }

  /**
   * {@inheritdoc}
   */
  protected function doDelete($entities) {
  }

  /**
   * {@inheritdoc}
   */
  public function save(EntityInterface $entity) {
  }

  /**
   * {@inheritdoc}
   */
  protected function getQueryServiceName() {
    return 'entity.query.sparql';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuery($conjunction = 'AND') {
    // Access the service directly rather than entity.query factory so the
    // storage's current entity type is used.
    $query = \Drupal::service($this->getQueryServiceName())->get($this->entityType, $conjunction);
    // dpm($query, '$query');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadRevisionFieldItems($revision_id) {
  }

  /**
   * {@inheritdoc}
   */
  protected function doSaveFieldItems(ContentEntityInterface $entity, array $names = []) {
  }

  /**
   * {@inheritdoc}
   */
  protected function doDeleteFieldItems($entities) {
  }

  /**
   * {@inheritdoc}
   */
  protected function doDeleteRevisionFieldItems(ContentEntityInterface $revision) {
  }

  /**
   * {@inheritdoc}
   */
  protected function readFieldItemsToPurge(FieldDefinitionInterface $field_definition, $batch_size) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  protected function purgeFieldItems(ContentEntityInterface $entity, FieldDefinitionInterface $field_definition) {
  }

  /**
   * {@inheritdoc}
   */
  protected function doSave($id, EntityInterface $entity) {
  }

  /**
   * {@inheritdoc}
   */
  protected function has($id, EntityInterface $entity) {
  }

  /**
   * {@inheritdoc}
   */
  public function countFieldData($storage_definition, $as_bool = FALSE) {
    return $as_bool ? FALSE : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function hasData() {
    return FALSE;
  }

}
