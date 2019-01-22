<?php

declare(strict_types = 1);

namespace Drupal\joinup_rss\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\rdf_entity\RdfEntitySparqlStorageInterface;
use Drupal\rdf_entity\RdfInterface;
use Drupal\rdf_entity\UriEncoder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide additional access checks for the collection feed view.
 */
class CollectionFeedController extends ControllerBase {

  /**
   * The SPARQL storage.
   *
   * @var \Drupal\rdf_entity\RdfEntitySparqlStorageInterface
   */
  private $sparqlStorage;

  /**
   * Instantiates a new CollectionFeedController object.
   *
   * @param \Drupal\rdf_entity\RdfEntitySparqlStorageInterface $sparql_storage
   *   The RDF entity storage.
   */
  public function __construct(RdfEntitySparqlStorageInterface $sparql_storage) {
    $this->sparqlStorage = $sparql_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('rdf_entity')
    );
  }

  /**
   * Access check for the collection feed view route.
   *
   * @param string $rdf_entity
   *   The encoded ID of an RDF entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result object.
   */
  public function access(string $rdf_entity): AccessResultInterface {
    $loaded_entity = $this->sparqlStorage->load(UriEncoder::decodeUrl($rdf_entity));

    return AccessResult::allowedIf($loaded_entity instanceof RdfInterface && $loaded_entity->bundle() === 'collection')
      ->addCacheableDependency($loaded_entity);
  }

}
