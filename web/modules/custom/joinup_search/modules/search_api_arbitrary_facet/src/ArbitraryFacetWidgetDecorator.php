<?php

declare(strict_types = 1);

namespace Drupal\search_api_arbitrary_facet;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\facets\FacetInterface;
use Drupal\facets\Widget\WidgetPluginInterface;
use Drupal\search_api_arbitrary_facet\Plugin\ArbitraryFacetManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Decorator for the arbitrary facet widget.
 */
class ArbitraryFacetWidgetDecorator implements WidgetPluginInterface, ContainerFactoryPluginInterface {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * The decorated widget.
   *
   * @var \Drupal\facets\Widget\WidgetPluginInterface
   */
  protected $original;

  /**
   * The arbitrary facet plugin manager.
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
   * @param \Drupal\Core\Cache\Context\CacheContextsManager $cache_contexts_manager
   *   The cache contexts manager service.
   * @param \Drupal\search_api_arbitrary_facet\Plugin\ArbitraryFacetManager $arbitrary_facet_manager
   *   The arbitrary facet plugin manager.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The dependency injection container.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, CacheContextsManager $cache_contexts_manager, ArbitraryFacetManager $arbitrary_facet_manager, ContainerInterface $container) {
    // Instantiate the decorated object.
    $class = $plugin_definition['decorated_class'];

    // If the plugin provides a factory method, pass the container to it.
    if (is_subclass_of($class, ContainerFactoryPluginInterface::class)) {
      $this->original = $class::create($container, $configuration, $plugin_id, $plugin_definition);
    }
    else {
      $this->original = new $class($configuration, $plugin_id, $plugin_definition, $cache_contexts_manager);
    }

    $this->arbitraryFacetManager = $arbitrary_facet_manager;
      $container->get('cache_contexts_manager'),
      $container->get('plugin.manager.arbitrary_facet'),
      $container
    );
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
  public function getQueryType() {
    $config = $this->original->getConfiguration();
    if (isset($config['arbitrary_facet_plugin']) && $config['arbitrary_facet_plugin'] != '_none') {
      return 'facet_query';
    }
    return $this->original->getQueryType();
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
    $options['_none'] = $this->t('-Disabled-');
    foreach ($definitions as $definition) {
      /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $label */
      $label = $definition['label'];
      $options[$definition['id']] = $label->render();
    }
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
    $default_config['arbitrary_facet_plugin'] = '_none';
    return $default_config;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return $this->original->calculateDependencies();
  }

  /**
   * {@inheritdoc}
   */
  public function supportsFacet(FacetInterface $facet) {
    return $this->original->supportsFacet($facet);
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
