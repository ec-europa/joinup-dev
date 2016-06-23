<?php

namespace Drupal\solution\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\og\OgAccess;
use Drupal\og\OgAccessInterface;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SolutionController.
 *
 * Handles the form to perform actions when it is called by a route that
 * includes an rdf_entity id.
 *
 * @package Drupal\solution\Controller
 */
class SolutionController extends ControllerBase {

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
    $solution = $this->createNewSolution($rdf_entity);

    $form = $this->entityFormBuilder()->getForm($solution);

    return $form;
  }

  /**
   * Handles access to the solution add form through collection pages.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The RDF entity for which the custom page is created.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result object.
   */
  public function createSolutionAccess(RdfInterface $rdf_entity) {
    return $this->ogAccess->userAccessEntity('create', $this->createNewSolution($rdf_entity), $this->currentUser());
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
  protected function createNewSolution(RdfInterface $rdf_entity) {
    return $this->entityTypeManager()->getStorage('rdf_entity')->create(array(
      'rid' => 'solution',
      'og_group_ref' => $rdf_entity->id(),
    ));
  }

}
