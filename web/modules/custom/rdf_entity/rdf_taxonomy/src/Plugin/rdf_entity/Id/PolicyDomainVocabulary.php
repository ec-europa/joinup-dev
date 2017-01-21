<?php

namespace Drupal\rdf_taxonomy\Plugin\rdf_entity\Id;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rdf_entity\RdfEntityIdPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generates the entity ID for 'policy_domain' taxonomy terms.
 *
 * @RdfEntityId(
 *   id = "policy_domain",
 *   name = @Translation("Policy domain terms"),
 *   bundles = {
 *     "taxonomy_term" = {
 *       "policy_domain",
 *     },
 *   },
 * )
 */
class PolicyDomainVocabulary extends RdfEntityIdPluginBase {

  /**
   * The transliteration service.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

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
      $pattern = 'http://joinup.eu/ontology/policy-domain/category#%s';
    }
    else {
      $pattern = 'http://joinup.eu/ontology/policy-domain#%s';
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
