<?php

declare(strict_types = 1);

namespace Drupal\joinup_core\Plugin\sparql_entity_storage\Id;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\sparql_entity_storage\SparqlEntityStorageEntityIdPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generates the entity ID for 'topic' taxonomy terms.
 *
 * @SparqlEntityIdGenerator(
 *   id = "topic",
 *   name = @Translation("Topic terms"),
 * )
 */
class TopicVocabulary extends SparqlEntityStorageEntityIdPluginBase {

  /**
   * The transliteration service.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * Constructs a TopicVocabulary plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   The transliteration service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, TransliterationInterface $transliteration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager);
    $this->transliteration = $transliteration;
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
      $container->get('transliteration')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function generate() {
    $entity = $this->getEntity();

    if (empty($entity->parent->target_id)) {
      $pattern = 'http://joinup.eu/ontology/topic/category#%s';
    }
    else {
      $pattern = 'http://joinup.eu/ontology/topic#%s';
    }

    $name = strtolower($this->transliteration->transliterate($entity->label()));
    // Replace non-alphanumeric characters with dash.
    $name = preg_replace('/[^a-z0-9]+/', '-', $name);
    // Strip consecutive dashes.
    $name = preg_replace('/\-{2,}/', '-', $name);
    // Trim dash from the begin and the end.
    $name = trim($name, '-');

    return sprintf($pattern, $name);
  }

}
