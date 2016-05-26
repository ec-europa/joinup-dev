<?php

namespace Drupal\rdf_entity\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\rdf_entity\RdfEntityTypeInterface;
use Drupal\rdf_entity\RdfInterface;

/**
 * Provides route responses for rdf_entity.module.
 */
class RdfController extends ControllerBase {

  /**
   * Route title callback.
   *
   * @param \Drupal\rdf_entity\RdfEntityTypeInterface $rdf_type
   *   The rdf type.
   *
   * @return array
   *   The rdf type label as a render array.
   */
  public function rdfTypeTitle(RdfEntityTypeInterface $rdf_type) {
    return [
      '#markup' => $rdf_type->label(),
      '#allowed_tags' => Xss::getHtmlTagList(),
    ];
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The rdf entity.
   *
   * @return array
   *   The rdf entity label as a render array.
   */
  public function rdfTitle(RdfInterface $rdf_entity) {
    return [
      '#markup' => $rdf_entity->getName(),
      '#allowed_tags' => Xss::getHtmlTagList(),
    ];
  }

  /**
   * Provides the RDF submission form.
   *
   * @param \Drupal\rdf_entity\RdfEntityTypeInterface $rdf_type
   *   The RDF bundle entity for the RDF entity.
   *
   * @return array
   *   A RDF submission form.
   */
  public function add(RdfEntityTypeInterface $rdf_type) {
    $rdf_entity = $this->entityManager()
      ->getStorage('rdf_entity')
      ->create(array(
        'rid' => $rdf_type->id(),
      ));

    $form = $this->entityFormBuilder()->getForm($rdf_entity, 'add');

    return $form;
  }

  /**
   * Displays add content links for available rdf types.
   *
   * Redirects to rdf_entity/add/[type] if only one rdf type is available.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array for a list of the rdf bundles that can be added; however,
   *   if there is only one rdf type defined for the site, the function
   *   will return a RedirectResponse to the rdf add page for that one rdf
   *   type.
   */
  public function addPage() {
    $build = [
      '#theme' => 'rdf_add_list',
      '#cache' => [
        'tags' => $this->entityManager()
          ->getDefinition('rdf_type')
          ->getListCacheTags(),
      ],
    ];

    $content = array();

    // Only use RDF types the user has access to.
    foreach ($this->entityManager()
               ->getStorage('rdf_type')
               ->loadMultiple() as $type) {
      $access = $this->entityManager()
        ->getAccessControlHandler('rdf_entity')
        ->createAccess($type->id(), NULL, [], TRUE);
      if ($access->isAllowed()) {
        $content[$type->id()] = $type;
      }
    }
    // Bypass the rdf_entity/add listing if only one content type is available.
    if (count($content) == 1) {
      $type = array_shift($content);
      return $this->redirect('rdf_entity.rdf_add', array('rdf_type' => $type->id()));
    }

    $build['#content'] = $content;

    return $build;
  }

}
