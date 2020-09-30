<?php

declare(strict_types = 1);

namespace Drupal\joinup_rdf_graph\Plugin\sparql_entity_storage\Id;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\sparql_entity_storage\SparqlEntityStorageEntityIdPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generates entity IDs for the 'rdf_graph' bundle.
 *
 * @SparqlEntityIdGenerator(
 *   id = "joinup_rdf_graph",
 *   name = @Translation("Joinup RDF Graph bundle ID generator."),
 * )
 */
class JoinupRdfGraphIdGenerator extends SparqlEntityStorageEntityIdPluginBase {

  /**
   * The UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuid;

  /**
   * Constructs a JoinupRdfEntityIdGenerator plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid
   *   The UUID service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, UuidInterface $uuid) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager);
    $this->uuid = $uuid;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('uuid')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function generate() {
    return "http://data.europa.eu/w21/user-graph/{$this->uuid->generate()}";
  }

}
