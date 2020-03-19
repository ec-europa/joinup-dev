<?php

declare(strict_types = 1);

namespace Drupal\joinup_seo\Plugin\simple_sitemap\UrlGenerator;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\joinup_group\JoinupGroupRelationInfoInterface;
use Drupal\simple_sitemap\EntityHelper;
use Drupal\simple_sitemap\Logger;
use Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\EntityUrlGenerator;
use Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\UrlGeneratorManager;
use Drupal\simple_sitemap\Simplesitemap;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Joinup Url Generators.
 */
abstract class JoinupUrlGeneratorBase extends EntityUrlGenerator {

  /**
   * The url generator manager service.
   *
   * @var \Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\UrlGeneratorManager
   */
  protected $urlGeneratorManager;

  /**
   * The Joinup group relation info service.
   *
   * @var \Drupal\joinup_group\JoinupGroupRelationInfoInterface
   */
  protected $relationInfo;

  /**
   * Constructs a JoinupUrlGeneratorBase object.
   *
   * @param array $configuration
   *   The configuration array.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition array.
   * @param \Drupal\simple_sitemap\Simplesitemap $generator
   *   The sitemap generator service.
   * @param \Drupal\simple_sitemap\Logger $logger
   *   The logger service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\simple_sitemap\EntityHelper $entityHelper
   *   The sitemap entity helper service.
   * @param \Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\UrlGeneratorManager $url_generator_manager
   *   The url generator manager service.
   * @param \Drupal\joinup_group\JoinupGroupRelationInfoInterface $relation_info
   *   The joinup group relation info service.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, Simplesitemap $generator, Logger $logger, LanguageManagerInterface $language_manager, EntityTypeManagerInterface $entity_type_manager, EntityHelper $entityHelper, UrlGeneratorManager $url_generator_manager, JoinupGroupRelationInfoInterface $relation_info) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $generator, $logger, $language_manager, $entity_type_manager, $entityHelper, $url_generator_manager);
    $this->urlGeneratorManager = $url_generator_manager;
    $this->relationInfo = $relation_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('simple_sitemap.generator'),
      $container->get('simple_sitemap.logger'),
      $container->get('language_manager'),
      $container->get('entity_type.manager'),
      $container->get('simple_sitemap.entity_helper'),
      $container->get('plugin.manager.simple_sitemap.url_generator'),
      $container->get('joinup_group.relation_info')
    );
  }

  /**
   * Returns an array of datasets to be processed.
   *
   * @return array
   *   An array of arrays, each of which contains the entity key and entity id.
   */
  public function getDataSets(): array {
    $data_sets = [];
    $sitemap_entity_types = $this->entityHelper->getSupportedEntityTypes();

    foreach ($this->generator->setVariants($this->sitemapVariant)->getBundleSettings() as $entity_type_id => $bundles) {
      if (isset($sitemap_entity_types[$entity_type_id])) {

        // Skip this entity type if another plugin is written to override its
        // generation.
        foreach ($this->urlGeneratorManager->getDefinitions() as $plugin) {
          if (isset($plugin['settings']['overrides_entity_type'])
            && $plugin['settings']['overrides_entity_type'] === $entity_type_id) {
            continue 2;
          }
        }

        $entityTypeStorage = $this->entityTypeManager->getStorage($entity_type_id);
        $keys = $sitemap_entity_types[$entity_type_id]->getKeys();

        foreach ($bundles as $bundle_name => $bundle_settings) {
          if (!empty($bundle_settings['index'])) {
            $query = $entityTypeStorage->getQuery();

            if (empty($keys['id'])) {
              $query->sort($keys['id'], 'ASC');
            }
            if (!empty($keys['bundle'])) {
              $query->condition($keys['bundle'], $bundle_name);
            }
            if (!empty($keys['status'])) {
              $query->condition($keys['status'], 1);
            }

            // Mainly this is the only line we need but we need to override the
            // whole method.
            $this->alterQuery($query, $entity_type_id, $bundle_name, $bundle_settings);

            foreach ($query->execute() as $entity_id) {
              $data_sets[] = [
                'entity_type' => $entity_type_id,
                'id' => $entity_id,
              ];
            }
          }
        }
      }
    }

    return $data_sets;
  }

  /**
   * {@inheritdoc}
   */
  protected function processDataSet($data_set) {
    if (empty($entity = $this->entityTypeManager->getStorage($data_set['entity_type'])->load($data_set['id']))) {
      return FALSE;
    }

    // In case the entity type is a node, we also need to also take into account
    // the status of the parent.
    if ($entity->getEntityTypeId() === 'node') {
      $parent = $this->relationInfo->getParent($entity);
      if (empty($parent) || !$parent->isPublished()) {
        return FALSE;
      }
    }

    // Not overriding the rest of the method will cause the entity to again be
    // loaded but at this point, it is stored in the static cache so there
    // should be minimal performance impact with better code readability.
    return parent::processDataSet($data_set);
  }

  /**
   * Alters the query that is responsible for fetching entities for the sitemap.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The query object before being executed.
   * @param string $entity_type_id
   *   The entity type id of the query.
   * @param string $bundle
   *   The entity bundle passed in the query.
   * @param array $bundle_settings
   *   The sitemap bundle settings.
   */
  abstract public function alterQuery(QueryInterface $query, string $entity_type_id, string $bundle, array $bundle_settings): void;

}
