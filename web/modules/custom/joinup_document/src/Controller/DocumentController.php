<?php

namespace Drupal\joinup_document\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\og\OgAccessInterface;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller that handles the form to add document to a collection.
 *
 * The parent is passed as a parameter from the route.
 *
 * @package Drupal\joinup_document\Controller
 */
class DocumentController extends ControllerBase {

  /**
   * The OG access handler.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * Constructs an DocumentController.
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
   * The main purpose is to automatically reference the parent group entity.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The collection or solution rdf_entity.
   *
   * @return array
   *   Return the form array to be rendered.
   */
  public function add(RdfInterface $rdf_entity) {
    $node = $this->createDocumentEntity($rdf_entity);
    $form = $this->entityFormBuilder()->getForm($node);

    return $form;
  }

  /**
   * Handles access to the document add form through RDF entity pages.
   *
   * Access is granted to moderators and group members that have the permission
   * to create document articles inside of their group, which in practice means
   * this is granted to collection and solution facilitators.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The RDF entity for which the document entity is created.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result object.
   */
  public function createDocumentAccess(RdfInterface $rdf_entity) {
    if (!in_array($rdf_entity->bundle(), ['collection', 'solution'])) {
      return AccessResult::forbidden();
    }
    $document = $this->createDocumentEntity($rdf_entity);
    return AccessResult::allowedIf(!empty($document->get('field_state')->first()->getTransitions()));
  }

  /**
   * Returns a document content entity.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *    The parent that the document content entity belongs to.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *    A node entity.
   */
  protected function createDocumentEntity(RdfInterface $rdf_entity) {
    return $this->entityTypeManager()->getStorage('node')->create([
      'type' => 'document',
      OgGroupAudienceHelperInterface::DEFAULT_FIELD => $rdf_entity->id(),
    ]);
  }

}
