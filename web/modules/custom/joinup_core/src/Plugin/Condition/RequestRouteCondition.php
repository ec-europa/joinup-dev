<?php

namespace Drupal\joinup_core\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Request Route' condition plugin.
 *
 * @Condition(
 *   id = "request_route",
 *   label = @Translation("Request route"),
 * )
 */
class RequestRouteCondition extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new RequestRouteCondition condition plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array('routes' => '') + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['routes'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Routes'),
      '#default_value' => $this->configuration['routes'],
      '#description' => $this->t('Specify routes by their name. Enter one path per line.'),
    );
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['routes'] = $form_state->getValue('routes');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $routes = $this->getRoutes();
    $routes = implode(', ', $routes);
    if (!empty($this->configuration['negate'])) {
      return $this->t('Do not return true on the following routes: @routes', array('@routes' => $routes));
    }
    return $this->t('Return true on the following routes: @routes', array('@routes' => $routes));
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $route_names = $this->getRoutes();
    $current_route_name = $this->routeMatch->getRouteName();

    return in_array($current_route_name, $route_names);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    $contexts[] = 'route.name';
    return $contexts;
  }

  /**
   * Returns the configured routes.
   *
   * The configuration value is saved as multi-line text field, so we need to
   * explode it and clean it.
   *
   * @return array
   *   The configured route names.
   */
  protected function getRoutes() {
    return array_map('trim', explode("\n", $this->configuration['routes']));
  }

}
