<?php

namespace Drupal\rdf_draft\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\rdf_entity\Entity\RdfEntitySparqlStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local task definitions for all entity bundles.
 */
class RdfDraftLocalTask extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Creates an RdfExportLocalTask object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, TranslationInterface $string_translation) {
    $this->entityManager = $entity_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = array();
    foreach ($this->entityManager->getDefinitions() as $entity_type_id => $entity_type) {
      $storage = $this->entityManager->getStorage($entity_type_id);
      if (!$storage instanceof RdfEntitySparqlStorage) {
        continue;
      }
      $definitions = $storage->getGraphsDefinition();
      unset($definitions['default']);
      foreach ($definitions as $name => $definition) {
        $has_export_path = $entity_type->hasLinkTemplate('rdf-draft-' . $name);
        if ($has_export_path) {
          $this->derivatives["entity.$entity_type_id.rdf_draft_$name"] = array(
            'route_name' => "entity.$entity_type_id.rdf_draft_$name",
            'title' => $this->t('View @graph', ['@graph' => $name]),
            'base_route' => "entity.$entity_type_id.canonical",
            'weight' => 100,
          );
        }
      }
    }
    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }
    return $this->derivatives;
  }

}
