<?php

namespace Drupal\joinup_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for joinup_core module.
 */
class CoreController extends ControllerBase {

  /**
   * Instantiates a CoreController instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
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
   * Renders the "about" view mode of a rdf entity.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The rdf entity to be rendered.
   *
   * @return array
   *   The "about" view mode render array.
   */
  public function aboutPage(RdfInterface $rdf_entity) {
    $page = $this->entityTypeManager->getViewBuilder('rdf_entity')->view($rdf_entity, 'about');

    $page['#entity_type'] = 'rdf_entity';
    $page['#rdf_entity'] = $rdf_entity;

    return $page;
  }

  /**
   * Returns the title for the about page.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The rdf entity being rendered.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The translated title.
   */
  public function aboutPageTitle(RdfInterface $rdf_entity) {
    return t('About @entity', ['@entity' => $rdf_entity->getName()]);
  }

}
