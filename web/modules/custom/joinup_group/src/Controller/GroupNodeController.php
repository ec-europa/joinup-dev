<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Controller;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\og\OgAccessInterface;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for group node content.
 */
class GroupNodeController extends ControllerBase {

  /**
   * The OG access service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * Constructs a new controller instance.
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
    return new static($container->get('og.access'));
  }

  /**
   * Provides a title callback for the group node add page.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The collection or solution RDF entity.
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The type of node to be added.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The title as a markup object.
   */
  public function addTitle(RdfInterface $rdf_entity, NodeTypeInterface $node_type): MarkupInterface {
    return $this->t('Add @type', ['@type' => $node_type->getSingularLabel()]);
  }

  /**
   * Builds a node creation form.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The collection or solution rdf_entity.
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The type of node to be added.
   *
   * @return array
   *   Return the form array to be rendered.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown when the user entity plugin definition is invalid.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown when the user entity type is not defined.
   */
  public function add(RdfInterface $rdf_entity, NodeTypeInterface $node_type): array {
    $node = $this->createNode($rdf_entity, $node_type);
    return $this->entityFormBuilder()->getForm($node);
  }

  /**
   * Handles access to the content add form through RDF entity pages.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The RDF entity for which the document entity is created.
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The type of node to be added.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The account to check access for. The current user will be used if NULL.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result object.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown when the user entity plugin definition is invalid.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown when the user entity type is not defined.
   */
  public function createAccess(RdfInterface $rdf_entity, NodeTypeInterface $node_type, AccountInterface $account): AccessResultInterface {
    // Grant access depending on whether the user has permission to create a
    // node in this group according to their OG role.
    return $this->ogAccess->userAccessGroupContentEntityOperation('create', $rdf_entity, $this->createNode($rdf_entity, $node_type), $account);
  }

  /**
   * Returns a node entity.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The parent that the community content entity belongs to.
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The type of node to be added.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A node entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown when the user entity plugin definition is invalid.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown when the user entity type is not defined.
   */
  protected function createNode(RdfInterface $rdf_entity, NodeTypeInterface $node_type) {
    return $this->entityTypeManager()->getStorage('node')->create([
      'type' => $node_type->id(),
      OgGroupAudienceHelperInterface::DEFAULT_FIELD => $rdf_entity->id(),
    ]);
  }

}
