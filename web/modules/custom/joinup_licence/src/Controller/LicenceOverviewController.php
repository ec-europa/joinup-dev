<?php

namespace Drupal\joinup_licence\Controller;

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
 *
 * @package Drupal\joinup_licence\Controller
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
   * @see Drupal\rdf_entity\Entity\Controller\RdfListBuilder
   *
   * @return string
   *   Return Hello string.
   */
  public function overview() {
    $mapping = $this->entityStorage->getRdfBundleList();
    if (!$mapping) {
      return [];
    }
    $query = $this->entityStorage->getQuery()
      ->condition('rid', 'licence');

    $header = $this->buildHeader();
    $query->tableSort($header);
    $rids = $query->execute();

    $licences = Rdf::loadMultiple($rids);
    $rows = [];
    foreach ($licences as $licence) {
      $rows[] = $this->buildRow($licence);
    }

    $build['table'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => array(),
      '#empty' => $this->t('There are no licences yet.'),
    );
    foreach ($licences as $licence) {
      if ($row = $this->buildRow($licence)) {
        $build['table']['#rows'][$licence->id()] = $row;
      }
    }

    return $build;
  }

  /**
   * Building the header and content lines for the Rdf list.
   */
  public function buildHeader() {
    $header = array(
      'id' => array(
        'data' => $this->t('URI'),
        'field' => 'id',
        'specifier' => 'id',
      ),
      'rid' => array(
        'data' => $this->t('Bundle'),
        'field' => 'rid',
        'specifier' => 'rid',
      ),
    );
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
