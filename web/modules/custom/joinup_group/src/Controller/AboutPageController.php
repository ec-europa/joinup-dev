<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\rdf_entity\RdfInterface;

/**
 * Route controller for the about page of a collection or solution.
 */
class AboutPageController extends ControllerBase {

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
  public function aboutPage(RdfInterface $rdf_entity): array {
    $page = $this->entityTypeManager()->getViewBuilder('rdf_entity')->view($rdf_entity, 'about');

    $page['#entity_type'] = 'rdf_entity';

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
  public function aboutPageTitle(RdfInterface $rdf_entity): TranslatableMarkup {
    return $this->t('About @entity', ['@entity' => $rdf_entity->getName()]);
  }

}
