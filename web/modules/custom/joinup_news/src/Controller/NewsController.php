<?php

namespace Drupal\joinup_news\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\og\OgAccessInterface;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller that handles the form to add news to a collection or a solution.
 *
 * The parent is passed as a parameter from the route.
 *
 * @package Drupal\joinup_news\Controller
 */
class NewsController extends ControllerBase {

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
   * The main purpose is to automatically reference the parent group entity.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The collection or solution rdf_entity.
   *
   * @return array
   *   Return the form array to be rendered.
   */
  public function add(RdfInterface $rdf_entity) {
    $node = $this->createNewsEntity($rdf_entity);
    $form = $this->entityFormBuilder()->getForm($node);

    return $form;
  }

  /**
   * Handles access to the news add form through RDF entity pages.
   *
   * Access is granted to moderators and group members that have the permission
   * to create news articles inside of their group, which in practice means this
   * is granted to collection and solution facilitators.
   *
   * @todo Depending on the 'eLibrary creation' setting, members should be able
   *   to create news.
   * @todo If a collection is open non-members should be able to create news.
   *
   * @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-2654
   * @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-2445
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The RDF entity for which the news entity is created.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result object.
   */
  public function createNewsAccess(RdfInterface $rdf_entity) {
    $user = $this->currentUser();
    // Grant access if the user is a moderator.
    if (in_array('moderator', $user->getRoles())) {
      return AccessResult::allowed()->addCacheContexts(['user.roles']);
    }
    // Grant access depending on whether the user has permission to create a
    // custom page according to their OG role.
    return $this->ogAccess->userAccessGroupContentEntityOperations('create', $rdf_entity, $this->createNewsEntity($rdf_entity), $user);
  }

  /**
   * Returns a news content entity.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *    The parent that the news content entity belongs to.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *    A node entity.
   */
  protected function createNewsEntity(RdfInterface $rdf_entity) {
    return $this->entityTypeManager()->getStorage('node')->create([
      'type' => 'news',
      OgGroupAudienceHelper::DEFAULT_FIELD => $rdf_entity->id(),
    ]);
  }

}
