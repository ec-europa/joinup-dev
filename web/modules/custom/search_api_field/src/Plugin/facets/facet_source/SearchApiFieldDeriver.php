<?php

declare(strict_types = 1);

namespace Drupal\search_api_field\Plugin\facets\facet_source;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\facets\FacetSource\FacetSourceDeriverBase;
use Drupal\field\Entity\FieldStorageConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derives a facet source plugin definition for every search api page.
 *
 * The definition of this plugin happens in facet_source\SearchApiPage, in this
 * deriver class we're actually getting all possible pages and creating plugins
 * for each of them.
 *
 * @see \Drupal\search_api_field\Plugin\facets\facet_source\SearchApiField
 */
class SearchApiFieldDeriver extends FacetSourceDeriverBase {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new SearchApiFieldDeriver.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, TranslationInterface $string_translation) {
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $plugin_derivatives = [];
    $base_plugin_id = $base_plugin_definition['id'];

    if (!isset($this->derivatives[$base_plugin_id])) {

      $map = $this->entityFieldManager->getFieldMapByFieldType('search_api_field');
      $ids = [];
      foreach ($map as $type => $info) {
        foreach ($info as $name => $data) {
          $ids[] = "$type.$name";
        }
      }

      $fs = FieldStorageConfig::loadMultiple($ids);
      /** @var \Drupal\field\Entity\FieldStorageConfig $field_config */
      foreach ($fs as $id => $field_config) {
        // Add plugin derivatives, they have 'search_api_field' as a special key
        // in them, because of this, there needs to happen less explode() magic
        // in the plugin class.
        $plugin_derivatives[$id] = [
          'id' => $base_plugin_id . PluginBase::DERIVATIVE_SEPARATOR . $id,
          'label' => $this->t('Search api field: %label (%id)', [
            '%label' => $field_config->label(),
            '%id' => $id,
          ]),
          'display_id' => $id,
          'description' => $this->t('Provides a facet source.'),
          'search_api_field' => $id,
        ] + $base_plugin_definition;
      }
      uasort($plugin_derivatives, [$this, 'compareDerivatives']);

      $this->derivatives[$base_plugin_id] = $plugin_derivatives;
    }
    return $this->derivatives[$base_plugin_id];
  }

}
