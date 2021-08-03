<?php

declare(strict_types = 1);

namespace Drupal\solution\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\collection\Entity\CommunityInterface;
use Drupal\rdf_entity\RdfInterface;
use Drupal\solution\Entity\SolutionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller providing the form to add a new solution inside a collection.
 */
class SolutionController extends ControllerBase {

  /**
   * Controller for the base form.
   *
   * We need to override the functionality of the create form for pages
   * that include the rdf_entity id in the url so that the og audience field
   * is auto completed.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The collection.
   *
   * @return array
   *   Return the form array to be rendered.
   */
  public function add(RdfInterface $rdf_entity): array {
    if (!$rdf_entity instanceof CommunityInterface) {
      throw new NotFoundHttpException();
    }
    $solution = $this->createNewSolution($rdf_entity);

    // Pass the collection to the form state so that the parent connection is
    // established.
    // @see solution_add_form_parent_submit()
    return $this->entityFormBuilder()->getForm($solution, 'default', ['collection' => $rdf_entity->id()]);
  }

  /**
   * Handles access to the solution add form through collection pages.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The collection in which the solution is created.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result object.
   */
  public function createSolutionAccess(RdfInterface $rdf_entity): AccessResultInterface {
    // If the collection is archived, content creation is not allowed.
    if (!$rdf_entity instanceof CommunityInterface || $rdf_entity->getWorkflowState() === 'archived') {
      return AccessResult::forbidden();
    }

    // Users with 'administer organic groups' permission should have access
    // since this page can only be called from within a group.
    $user = $this->currentUser();
    if ($user->hasPermission('administer organic groups')) {
      return AccessResult::allowed();
    }

    return $rdf_entity->hasGroupPermission((int) $user->id(), 'create solution rdf_entity') ? AccessResult::allowed() : AccessResult::forbidden();
  }

  /**
   * Creates a new solution entity that is affiliated with the given collection.
   *
   * @param \Drupal\collection\Entity\CommunityInterface $community
   *   The collection to affiliate with the new solution.
   *
   * @return \Drupal\solution\Entity\SolutionInterface
   *   The unsaved solution entity.
   */
  protected function createNewSolution(CommunityInterface $community): SolutionInterface {
    return $this->entityTypeManager()->getStorage('rdf_entity')->create([
      'rid' => 'solution',
      'collection' => [$community->id()],
    ]);
  }

}
