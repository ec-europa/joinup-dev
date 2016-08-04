<?php

namespace Drupal\custom_page\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\og\OgAccessInterface;
use Drupal\og\OgGroupAudienceHelper;
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
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The RDF entity for which the custom page is created.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result object.
   */
  public function createCustomPageAccess(RdfInterface $rdf_entity) {
    return $this->ogAccess->userAccessGroupContentEntityOperations('create', $rdf_entity, $this->createNewCustomPage($rdf_entity), $this->currentUser());
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
      OgGroupAudienceHelper::DEFAULT_FIELD => $rdf_entity->id(),
    ]);
  }

}
