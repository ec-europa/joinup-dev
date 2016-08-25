<?php

namespace Drupal\joinup_discussion\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\og\OgAccessInterface;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller that handles the form to add discussion to a collection.
 *
 * The parent is passed as a parameter from the route.
 *
 * @package Drupal\joinup_discussion\Controller
 */
class DiscussionController extends ControllerBase {

  /**
   * The OG access handler.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * Constructs an DiscussionController.
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
    $node = $this->createDiscussionEntity($rdf_entity);
    $form = $this->entityFormBuilder()->getForm($node);

    return $form;
  }

  /**
   * Handles access to the discussion add form through RDF entity pages.
   *
   * Access is granted to moderators and group members that have the permission
   * to create discussion articles inside of their group, which in practice
   * means this is granted to collection facilitators.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The RDF entity for which the discussion entity is created.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result object.
   */
  public function createDiscussionAccess(RdfInterface $rdf_entity) {
    $user = $this->currentUser();
    // Grant access if the user is a moderator.
    if (in_array('moderator', $user->getRoles())) {
      return AccessResult::allowed()->addCacheContexts(['user.roles']);
    }
    // Grant access depending on whether the user has permission to create a
    // custom page according to their OG role.
    return $this->ogAccess->userAccessGroupContentEntityOperations('create', $rdf_entity, $this->createDiscussionEntity($rdf_entity), $user);
  }

  /**
   * Returns a discussion content entity.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *    The parent that the discussion content entity belongs to.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *    A node entity.
   */
  protected function createDiscussionEntity(RdfInterface $rdf_entity) {
    return $this->entityTypeManager()->getStorage('node')->create([
      'type' => 'discussion',
      OgGroupAudienceHelper::DEFAULT_FIELD => $rdf_entity->id(),
    ]);
  }

}
