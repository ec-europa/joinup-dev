<?php

declare(strict_types = 1);

namespace Drupal\joinup_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\og\OgContextInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a global search block.
 *
 * This block is used in the top header as well as on the front page. It allows
 * to do a site-wide (a.k.a. 'global') search. When displayed on a page that
 * belongs to a group, the group will be added as a filter which is displayed
 * inline in the text field.
 *
 * @Block(
 *   id = "joinup_search_global_search",
 *   admin_label = @Translation("Global search")
 * )
 */
class GlobalSearchBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The OG context provider.
   *
   * @var \Drupal\og\OgContextInterface
   */
  protected $ogContext;

  /**
   * Constructs a GlobalSearchBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\og\OgContextInterface $og_context
   *   The OG context provider.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, OgContextInterface $og_context) {
    $this->ogContext = $og_context;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('og.context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'template_suggestion' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['template_suggestion'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Template suggestion'),
      '#default_value' => $this->configuration['template_suggestion'],
      '#size' => 60,
      '#maxlength' => 128,
      '#pattern' => '[a-z_]+',
      '#description' => $this->t('Optional template suggestion, can be used to override the theming. Can consist only of lowercase letters and underscores.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['template_suggestion'] = $form_state->getValue('template_suggestion');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $group = $this->getGroup();

    $filters = $group ? ['group:' . $group->id() => $group->label()] : [];

    $build['content'] = [
      '#theme' => 'joinup_search_global_search',
      '#filters' => $filters,
      '#template_suggestion' => $this->configuration['template_suggestion'],
      '#cache' => [
        'contexts' => $this->getCacheContexts(),
        'tags' => $this->getCacheTags(),
      ],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // This varies by group context since on group pages the search field is
    // prepopulated with a filter on the current group.
    return Cache::mergeContexts(parent::getCacheContexts(), ['og_group_context']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $group = $this->getGroup();

    $cache_tags = $group ? $group->getCacheTags() : [];
    return Cache::mergeTags(parent::getCacheTags(), $cache_tags);
  }

  /**
   * Returns the group that is active in the current context.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The group, or NULL if no group is currently active.
   */
  protected function getGroup(): ?EntityInterface {
    return $this->ogContext->getGroup();
  }

}
