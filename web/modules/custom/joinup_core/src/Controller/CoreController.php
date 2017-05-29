<?php

namespace Drupal\joinup_core\Controller;

use Drupal\Core\Access\AccessResult;
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
   * We cannot use the default entity view controller, as they force the entity
   * title as the page title.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The rdf entity to be rendered.
   *
   * @return array
   *   The "about" view mode render array.
   *
   * @see \Drupal\Core\Entity\Controller\EntityViewController::buildTitle()
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

  /**
   * Additional access check for the rdf entity "about" page.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The rdf entity being checked.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function aboutPageAccess(RdfInterface $rdf_entity) {
    return AccessResult::allowedIf(in_array($rdf_entity->bundle(), ['collection', 'solution']));
  }

}
