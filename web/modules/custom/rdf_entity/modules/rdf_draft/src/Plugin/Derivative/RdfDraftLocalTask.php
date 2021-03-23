<?php

declare(strict_types = 1);

namespace Drupal\rdf_draft\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\sparql_entity_storage\SparqlEntityStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local task definitions for all entity bundles.
 */
class RdfDraftLocalTask extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates an RdfExportLocalTask object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      $storage = $this->entityTypeManager->getStorage($entity_type_id);
      if (!$storage instanceof SparqlEntityStorage) {
        continue;
      }
      $definitions = $storage->getGraphDefinitions();
      unset($definitions['default']);
      foreach ($definitions as $name => $definition) {
        $has_export_path = $entity_type->hasLinkTemplate('rdf-draft-' . $name);
        if ($has_export_path) {
          $this->derivatives["entity.$entity_type_id.rdf_draft_$name"] = [
            'route_name' => "entity.$entity_type_id.rdf_draft_$name",
            'title' => $this->t('View @graph', ['@graph' => $name]),
            'base_route' => "entity.$entity_type_id.canonical",
            'weight' => 100,
          ] + $base_plugin_definition;
        }
      }
    }
    return $this->derivatives;
  }

}
