<?php

namespace Drupal\joinup\Controller;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\rdf_entity\RdfEntityTypeInterface;

/**
 * Provides route responses for Joinup.
 */
class JoinupController extends ControllerBase {

  /**
   * Provides propose forms for various RDF entities.
   *
   * This is used for the propose form of collections, and will be used for
   * interoperability solutions in the future.
   *
   * @param \Drupal\rdf_entity\RdfEntityTypeInterface $rdf_type
   *   The RDF bundle entity for which to generate the propose form.
   *
   * @return array
   *   A render array for the propose form.
   */
  public function proposeRdfEntity(RdfEntityTypeInterface $rdf_type) {
    $rdf_entity = $this->entityTypeManager()->getStorage('rdf_entity')->create(array(
      'rid' => $rdf_type->id(),
    ));

    $form = $this->entityFormBuilder()->getForm($rdf_entity, 'propose');
    $form['#title'] = $this->t('Propose @type', [
      '@type' => Unicode::strtolower($rdf_type->label()),
    ]);
    return $form;
  }

  /**
   * Provides empty homepage..
   *
   * @return array
   *   A render array for the homepage.
   */
  public function homepageContent() {
    $build = array();
    return $build;
  }

}
