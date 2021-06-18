<?php

declare(strict_types = 1);

namespace Drupal\joinup_core\Plugin\Condition;

use Drupal\Component\Render\MarkupInterface;
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
 *
 * @todo Replace this with the Route Condition module.
 *
 * @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-6062
 * @see https://www.drupal.org/project/route_condition
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
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
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
  public function defaultConfiguration(): array {
    return [
      'routes' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['routes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Routes'),
      '#default_value' => implode("\n", $this->getConfiguration()['routes']),
      '#description' => $this->t('Specify routes by their name. Enter one path per line.'),
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $routes = array_values(array_filter(array_map('trim', explode("\n", str_replace("\r", "\n", $form_state->getValue('routes'))))));
    $this->setConfig('routes', $routes);
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary(): MarkupInterface {
    $routes = implode(', ', $this->getConfiguration()['routes']);
    if (!empty($this->isNegated())) {
      return $this->t('Do not return true on the following routes: @routes', ['@routes' => $routes]);
    }
    return $this->t('Return true on the following routes: @routes', ['@routes' => $routes]);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(): bool {
    return in_array($this->routeMatch->getRouteName(), $this->getConfiguration()['routes'], TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    $contexts[] = 'route.name';
    return $contexts;
  }

}
