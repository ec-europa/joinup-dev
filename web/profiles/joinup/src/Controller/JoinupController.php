<?php

declare(strict_types = 1);

namespace Drupal\joinup\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for Joinup.
 */
class JoinupController extends ControllerBase {

  /**
   * Provides propose forms for rdf entities.
   *
   * This is used for the propose form of collections.
   *
   * @return array
   *   A render array for the propose form.
   */
  public function proposeRdfEntity() {
    $rdf_entity = $this->entityTypeManager()->getStorage('rdf_entity')->create([
      'rid' => 'collection',
    ]);

    $form = $this->entityFormBuilder()->getForm($rdf_entity, 'propose');
    $form['#title'] = $this->t('Propose @type', [
      '@type' => mb_strtolower($rdf_entity->rid->entity->getSingularLabel()),
    ]);
    return $form;
  }

  /**
   * Handles access to the rdf_entity proposal form.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result object.
   */
  public function createAccess() {
    return AccessResult::allowedIf($this->currentUser()->hasPermission("propose collection rdf entity"));
  }

  /**
   * Provides a page outlining eligibility criteria for solutions.
   *
   * @return array
   *   The page as a render array.
   */
  public function eligibilityCriteria() {
    return ['#theme' => 'joinup_eligibility_criteria'];
  }

}
