<?php

namespace Drupal\rdf_entity\Plugin\rdf_entity\Id;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rdf_entity\RdfEntityIdPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a fallback entity ID generator plugin.
 *
 * @RdfEntityId(
 *   id = "default",
 *   name = @Translation("Default ID generator"),
 * )
 */
class DefaultRdfEntityIdGenerator extends RdfEntityIdPluginBase {

  /**
   * The UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuid;

  /**
   * Constructs a PolicyDomainVocabulary plugin.
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
    global $base_url;

    $entity_type_id = $this->getEntity()->getEntityTypeId();
    $bundle = $this->getEntity()->bundle();
    $uuid = $this->uuid->generate();

    return "$base_url/$entity_type_id/$bundle/$uuid";
  }

}
