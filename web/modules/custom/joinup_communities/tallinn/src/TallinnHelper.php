<?php

namespace Drupal\tallinn;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides helper methods for the tallinn module.
 */
class TallinnHelper implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a TallinnHelper instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $configFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * Returns the tallinn collection entity id.
   *
   * @return string
   *   The tallinn collection entity id.
   */
  public function getTallinnEntityId() {
    return $this->configFactory->get('tallinn.settings')->get('tallinn_id');
  }

  /**
   * Returns the tallinn collection entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The tallinn collection entity or null if not found.
   */
  public function getTallinnEntity() {
    $tallinn_id = $this->getTallinnEntityId();
    return $this->entityTypeManager->getStorage('rdf_entity')->load($tallinn_id);
  }

}
