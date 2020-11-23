<?php

namespace Drupal\easme_covid19\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block with the Covid 19 community content for the current user.
 *
 * This is the block that is responsible for the content and tiles that are
 * shown on the homepage.
 *
 * @Block(
 *  id = "covid19_content",
 *  admin_label = @Translation("Covid 19 content"),
 * )
 */
class Covid19ContentBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new RecommendedContentBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [
      'header' => [
        '#type' => 'inline_template',
        '#template' => '<p>{% trans %}EIC COVID-19 is a platform created by the European Commission as a follow up to the COVID-19 challenges presented at the <a href="https://euvsvirus.org/">EUvsVIRUS Hackathon</a>. The platform is a collaborative space where public and private procurers, local / regional / national organisations and agencies can setup challenges. Here innovators, companies, researchers can forward their solutions. Sponsors have the possibility to pledge their support. Funded by the European Union via the European Innovation Council (EIC) programme. It offers several services that aim at helping all relevant actors from the hackathon to continue and expand their collaboration with each other, reaching innovative and fast solutions to all relevant challenges. {% endtrans %}</p>',
      ],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // The block is dependent on the user's groups.
    return Cache::mergeContexts(parent::getCacheContexts(), ['og_role']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $menu = $this->entityTypeManager->getStorage('menu')->load('front-page');
    $cache_tags = Cache::mergeTags(parent::getCacheTags(), ['node_list', 'rdf_entity_list']);
    // The block should be invalidated whenever any node changes.
    return Cache::mergeTags($cache_tags, $menu->getCacheTags());
  }

}
