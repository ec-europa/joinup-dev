<?php

namespace Drupal\search_api_arbitrary_facet;

use Drupal\facets\Widget\WidgetPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class ArbitraryFacetWidgetDecorator.
 *
 * @package Drupal\search_api_arbitrary_facet
 */
class ArbitraryFacetWidgetDecorator implements WidgetPluginInterface {
  use StringTranslationTrait;

  /**
   * The decorated widget.
   *
   * @var \Drupal\facets\Widget\WidgetPluginInterface
   */
  protected $original;

  /**
   * The arbitratry facet plugin manager.
   *
   * @var \Drupal\search_api_arbitrary_facet\Plugin\ArbitraryFacetManager
   */
  protected $arbitraryFacetManager;

  /**
   * Constructs decorated widget object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    // Instantiate the decorated object.
    $class = $plugin_definition['decorated_class'];
    $this->original = new $class($configuration, $plugin_id, $plugin_definition);
    $this->arbitraryFacetManager = \Drupal::getContainer()->get('plugin.manager.arbitrary_facet');
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet) {
    return $this->original->build($facet);
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryType(array $query_types) {
    $config = $this->original->getConfiguration();
    if (isset($config['arbitrary_facet_enabled']) && $config['arbitrary_facet_enabled']) {
      return 'facet_query';
    }
    return $this->original->getQueryType($query_types);
  }

  /**
   * {@inheritdoc}
   */
  public function isPropertyRequired($name, $type) {
    return $this->original->isPropertyRequired($name, $type);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $form = $this->original->buildConfigurationForm($form, $form_state, $facet);
    $definitions = $this->arbitraryFacetManager->getDefinitions();
    $config = $this->getConfiguration();
    $default_config = $this->defaultConfiguration();
    $options = [];
    foreach ($definitions as $definition) {
      /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $label */
      $label = $definition['label'];
      $options[$definition['id']] = $label->render();
    }
    $form['arbitrary_facet_enabled'] = [
      '#title' => $this->t("Enable arbitrary facet"),
      '#type' => 'checkbox',
      '#default_value' => isset($config['arbitrary_facet_enabled']) ? $config['arbitrary_facet_enabled'] : $default_config['arbitrary_facet_enabled'],
    ];
    $form['arbitrary_facet_plugin'] = [
      '#title' => $this->t("Arbitrary facet plugin"),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => isset($config['arbitrary_facet_plugin']) ? $config['arbitrary_facet_plugin'] : $default_config['arbitrary_facet_plugin'],
      '#description' => $this->t("Implement a 'ArbitraryFacet' plugin to define your own arbitrary facet."),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->original->getConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    return $this->original->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default_config = $this->original->defaultConfiguration();
    $default_config['arbitrary_facet_plugin'] = 'default';
    $default_config['arbitrary_facet_enabled'] = 0;
    return $default_config;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return $this->original->calculateDependencies();
  }

  /**
   * Pass all missing methods to the decorated object.
   *
   * @param string $method
   *   Method name.
   * @param array $args
   *   Method arguments.
   *
   * @return mixed
   *   Method return value.
   */
  public function __call($method, array $args) {
    return call_user_func_array([$this->original, $method], $args);
  }

}
