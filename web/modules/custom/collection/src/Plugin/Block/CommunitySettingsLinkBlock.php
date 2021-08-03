<?php

declare(strict_types = 1);

namespace Drupal\collection\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\collection\Entity\CommunityInterface;
use Drupal\og\OgContextInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block plugin to show the collection settings link.
 *
 * @Block(
 *   id = "collection_settings_link",
 *   admin_label = @Translation("Community settings link"),
 * )
 */
class CommunitySettingsLinkBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The OG context service.
   *
   * @var \Drupal\og\OgContextInterface
   */
  protected $ogContext;

  /**
   * Constructs a new plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\og\OgContextInterface $og_context
   *   The OG context service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, OgContextInterface $og_context) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->ogContext = $og_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
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
  public function build(): array {
    $build = [];
    $cache_metadata = (new CacheableMetadata())->addCacheContexts(['route']);

    $community = $this->ogContext->getGroup();
    if ($community && $community instanceof CommunityInterface) {
      $cache_metadata->addCacheableDependency($community);
      $url = Url::fromRoute('collection.settings_form', [
        'rdf_entity' => $community->id(),
      ]);
      $build['link'] = [
        '#type' => 'link',
        '#title' => $this->t('Glossary settings'),
        '#url' => $url,
        '#access' => $url->access(),
        '#attributes' => [
          'class' => ['glossary-settings-link'],
        ],
      ];
    }
    $cache_metadata->applyTo($build);

    return $build;
  }

}
