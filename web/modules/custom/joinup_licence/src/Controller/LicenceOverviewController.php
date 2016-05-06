<?php

namespace Drupal\joinup_licence\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\rdf_entity\Entity\Rdf;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Class LicenceOverviewController.
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
   * Build.
   *
   * @return string
   *   Return Hello string.
   */
  public function build() {
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
   * {@inheritdoc}
   *
   * Building the header and content lines for the Rdf list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
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
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\rdf_entity\Entity\Rdf */
    $row['id'] = $entity->link();
    $row['rid'] = $entity->bundle();
    return $row;
  }

}
