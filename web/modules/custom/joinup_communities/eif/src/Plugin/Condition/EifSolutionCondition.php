<?php

declare(strict_types = 1);

namespace Drupal\eif\Plugin\Condition;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\eif\EifInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'EIF Toolbox Solutions' condition plugin.
 *
 * @Condition(
 *   id = "eif_solutions_page",
 *   label = @Translation("EIF Toolbox Solutions"),
 * )
 */
class EifSolutionCondition extends ConditionPluginBase implements ContainerFactoryPluginInterface {

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
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, RouteMatchInterface $route_match) {
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
      'eif_solutions' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['eif_solutions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('EIF Toolbox Solutions page'),
      '#default_value' => $this->getConfiguration()['eif_solutions'],
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->setConfig('eif_solutions', $form_state->getValue('eif_solutions'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary(): MarkupInterface {
    if ($this->isNegated()) {
      return $this->t('Do not return true on EIF Toolbox Solutions page');
    }
    return $this->t('Return true on EIF Toolbox Solutions page');
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(): bool {
    if (!$this->getConfiguration()['eif_solutions']) {
      return FALSE;
    }
    $route_name = $this->routeMatch->getRouteName();
    return $route_name === 'eif.solutions' || ($route_name === 'entity.node.canonical' && (int) $this->routeMatch->getRawParameter('node') === EifInterface::EIF_SOLUTIONS_NID);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

}
