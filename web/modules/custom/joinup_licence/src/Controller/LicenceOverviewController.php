<?php

namespace Drupal\joinup_licence\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\rdf_entity\Entity\Rdf;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Displays an overview of the licence entities to the user.
 *
 * This overview replaces the system's content overview so that we can
 * show the entities to user with no access to the admin area.
 */
class LicenceOverviewController extends ControllerBase {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The rdf entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityStorage = $entity_type_manager->getStorage('rdf_entity');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Builds the overview of the licence entities.
   *
   * This is mimicking the rdf_entity's overview builder class.
   *
   * @see \Drupal\rdf_entity\Entity\Controller\RdfListBuilder
   *
   * @return array
   *   The build render array.
   */
  public function overview() {
    $query = $this->entityStorage->getQuery()
      ->condition('rid', 'licence');

    $header = $this->buildHeader();
    $query->tableSort($header);
    $rids = $query->execute();

    $cacheable_metadata = (new CacheableMetadata())
      // Tag the response cache with rdf_entity_list:licence so that this page
      // cache is invalidated when a new licence is added.
      // @see joinup_core_rdf_entity_insert()
      ->addCacheTags(['rdf_entity_list:licence']);

    $rows = [];
    foreach (Rdf::loadMultiple($rids) as $id => $licence) {
      if ($row = $this->buildRow($licence)) {
        $rows[$id] = $row;
        // The list cache should be invalidated on each licence change/delete.
        $cacheable_metadata->addCacheableDependency($licence);
      }
    }

    $build = [
      [
        '#type' => 'table',
        '#header' => $this->buildHeader(),
        '#rows' => $rows,
        '#empty' => $this->t('There are no licences yet.'),
      ],
    ];

    // Add cacheable metadata.
    $cacheable_metadata->applyTo($build);

    return $build;
  }

  /**
   * Building the header and content lines for the Rdf list.
   */
  public function buildHeader() {
    $header = [
      'id' => [
        'data' => $this->t('URI'),
        'field' => 'id',
        'specifier' => 'id',
      ],
      'rid' => [
        'data' => $this->t('Bundle'),
        'field' => 'rid',
        'specifier' => 'rid',
      ],
    ];
    return $header;
  }

  /**
   * Builds a table row for a licence rdf_entity.
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\rdf_entity\Entity\Rdf */
    $row['id'] = $entity->link();
    $row['rid'] = $entity->bundle();
    return $row;
  }

}
