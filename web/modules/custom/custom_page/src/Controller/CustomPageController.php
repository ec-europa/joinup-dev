<?php

namespace Drupal\custom_page\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\OgAccessInterface;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\og_menu\OgMenuInstanceInterface;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CustomPageController.
 *
 * Handles the form to perform actions when it is called by a route that
 * includes an rdf_entity id.
 *
 * @package Drupal\custom_page\Controller
 */
class CustomPageController extends ControllerBase {

  /**
   * The OG access handler.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * Constructs a CustomPageController.
   *
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access handler.
   */
  public function __construct(OgAccessInterface $og_access) {
    $this->ogAccess = $og_access;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('og.access')
    );
  }

  /**
   * Controller for the base form.
   *
   * We need to override the functionality of the create form for pages
   * that include the rdf_entity id in the url so that the og audience field
   * is auto completed.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The collection rdf_entity.
   *
   * @return array
   *   Return the form array to be rendered.
   */
  public function add(RdfInterface $rdf_entity) {
    $node = $this->createNewCustomPage($rdf_entity);
    $form = $this->entityFormBuilder()->getForm($node);

    return $form;
  }

  /**
   * Handles access to the custom page add form through collection pages.
   *
   * Access is granted to moderators and group members that have the permission
   * to create custom pages inside of their group, which in practice means this
   * is granted to collection and solution facilitators.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The RDF entity for which the custom page is created.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result object.
   */
  public function createCustomPageAccess(RdfInterface $rdf_entity, AccountInterface $account) {
    // Grant access if the user is a moderator.
    if (in_array('moderator', $account->getRoles())) {
      return AccessResult::allowed()->addCacheContexts(['user.roles']);
    }
    // Grant access depending on whether the user has permission to create a
    // custom page according to their OG role.
    return $this->ogAccess->userAccessGroupContentEntityOperation('create', $rdf_entity, $this->createNewCustomPage($rdf_entity), $account);
  }

  /**
   * Creates a new custom page entity.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The collection with which the custom page will be associated.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The unsaved custom page entity.
   */
  protected function createNewCustomPage(RdfInterface $rdf_entity) {
    return $this->entityTypeManager()->getStorage('node')->create([
      'type' => 'custom_page',
      OgGroupAudienceHelperInterface::DEFAULT_FIELD => $rdf_entity->id(),
    ]);
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
