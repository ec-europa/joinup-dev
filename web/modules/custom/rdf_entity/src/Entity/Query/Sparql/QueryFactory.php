<?php

namespace Drupal\rdf_entity\Entity\Query\Sparql;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\Entity\Query\QueryFactoryInterface;
use Drupal\rdf_entity\Database\Driver\sparql\Connection;

/**
 * Provides a factory for creating entity query objects for the null backend.
 */
class QueryFactory implements QueryFactoryInterface {

  /**
   * The namespace of this class, the parent class etc.
   *
   * @var array
   */
  protected $namespaces;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a QueryFactory object.
   *
   * @param \Drupal\rdf_entity\Database\Driver\sparql\Connection $connection
   *    The connection object
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *    The entity type manager.
   */
  public function __construct(Connection $connection, EntityTypeManager $entity_type_manager) {
    $this->connection = $connection;
    $this->namespaces = QueryBase::getNamespaces($this);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function get(EntityTypeInterface $entity_type, $conjunction) {
    $class = QueryBase::getClass($this->namespaces, 'Query');
    return new $class($entity_type, $conjunction, $this->connection, $this->namespaces, $this->entityTypeManager);
  }

  /**
   * {@inheritdoc}
   */
  public function getAggregate(EntityTypeInterface $entity_type, $conjunction) {
    $class = QueryBase::getClass($this->namespaces, 'Query');
    return new $class($entity_type, $conjunction, $this->connection, $this->namespaces, $this->entityTypeManager);
  }

}
