<?php

namespace Drupal\custom_page\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Access\AccessResult;
use Drupal\joinup_core\Controller\CommunityContentController;
use Drupal\og_menu\OgMenuInstanceInterface;
use Drupal\rdf_entity\RdfInterface;

/**
 * Class CustomPageController.
 *
 * Handles the form to perform actions when it is called by a route that
 * includes an rdf_entity id.
 *
 * @package Drupal\custom_page\Controller
 */
class CustomPageController extends CommunityContentController {

  /**
   * {@inheritdoc}
   *
   * The custom pages are only allowed to be created for collections.
   */
  public function createAccess(RdfInterface $rdf_entity) {
    if ($rdf_entity->bundle() !== 'collection') {
      return AccessResult::forbidden();
    }
    // Grant access depending on whether the user has permission to create a
    // custom page according to their OG role.
    return $this->ogAccess->userAccessGroupContentEntityOperation('create', $rdf_entity, $this->createContentEntity($rdf_entity));
  }

  /**
   * {@inheritdoc}
   */
  protected function getBundle() {
    return 'custom_page';
  }

  /**
   * Altered title callback for the navigation menu edit form.
   *
   * @param \Drupal\og_menu\OgMenuInstanceInterface $ogmenu_instance
   *   The OG Menu instance that is being edited.
   *
   * @return array
   *   The title as a render array.
   *
   * @see \Drupal\custom_page\Routing\RouteSubscriber::alterRoutes()
   */
  public function editFormTitle(OgMenuInstanceInterface $ogmenu_instance) {
    // Provide a custom title for the OG Menu instance edit form. The default
    // menu is suitable for webmasters, but we need a simpler title since this
    // form is exposed to regular visitors.
    $group = $ogmenu_instance->og_audience->entity;
    return [
      '#markup' => t('Edit navigation menu of the %group @type', [
        '%group' => $ogmenu_instance->label(),
        '@type' => $group->bundle(),
      ]),
      '#allowed_tags' => Xss::getHtmlTagList(),
    ];
  }

}
