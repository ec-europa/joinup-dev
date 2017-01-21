<?php

namespace Drupal\rdf_taxonomy\Plugin\rdf_entity\Id;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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
class PolicyDomainVocabulary extends RdfEntityIdPluginBase implements ContainerFactoryPluginInterface {

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
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   The transliteration service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TransliterationInterface $transliteration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('transliteration')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function generate() {
    $entity = $this->getEntity();
    $pattern = empty($entity->parent->target_id) ? 'http://joinup.eu/ontology/policy-domain/category#%s' : 'http://joinup.eu/ontology/policy-domain/category#%s';
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
