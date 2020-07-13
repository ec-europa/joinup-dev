<?php

declare(strict_types = 1);

namespace Drupal\joinup_community_content\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\joinup_community_content\CommunityContentWorkflowAccessControlHandler;
use Drupal\og\OgAccessInterface;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller that handles the form to add community content to a collection.
 *
 * The parent is passed as a parameter from the route.
 */
abstract class CommunityContentController extends ControllerBase {

  /**
   * The OG access handler.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * The node workflow access control handler.
   *
   * @var \Drupal\joinup_community_content\CommunityContentWorkflowAccessControlHandler
   */
  protected $workflowAccessControlHandler;

  /**
   * Constructs a CommunityContentController.
   *
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access handler.
   * @param \Drupal\joinup_community_content\CommunityContentWorkflowAccessControlHandler $workflow_access_control_handler
   *   The node workflow access control handler.
   */
  public function __construct(OgAccessInterface $og_access, CommunityContentWorkflowAccessControlHandler $workflow_access_control_handler) {
    $this->ogAccess = $og_access;
    $this->workflowAccessControlHandler = $workflow_access_control_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('og.access'),
      $container->get('joinup_community_content.community_content_workflow_access')
    );
  }

  /**
   * Constructs a create form for community content.
   *
   * The main purpose is to automatically reference the parent group entity.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The collection or solution rdf_entity.
   *
   * @return array
   *   Return the form array to be rendered.
   */
  public function add(RdfInterface $rdf_entity): array {
    $node = $this->createContentEntity($rdf_entity);
    return $this->entityFormBuilder()->getForm($node);
  }

  /**
   * Handles access to the content add form through RDF entity pages.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The RDF entity for which the document entity is created.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The account to check access for. The current user will be used if NULL.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result object.
   */
  public function createAccess(RdfInterface $rdf_entity, ?AccountInterface $account = NULL): AccessResult {
    if (empty($account)) {
      $account = $this->currentUser();
    }
    if (!in_array($rdf_entity->bundle(), ['collection', 'solution'])) {
      return AccessResult::forbidden();
    }

    // If the collection is archived, content creation is not allowed.
    if ($rdf_entity->bundle() === 'collection' && $rdf_entity->field_ar_state->first()->value === 'archived') {
      return AccessResult::forbidden();
    }

    // @todo This is probably related to ISAICP-6007. The 'create aceess' check
    // shouldn't require an entity. The entity is created only after the 'create
    // access' is checked. Refactor this in ISAICP-6007.
    // @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-6007
    $content = $this->createContentEntity($rdf_entity);
    return $this->workflowAccessControlHandler->entityAccess($content, 'create', $account);
  }

  /**
   * Returns a community content entity.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The parent that the community content entity belongs to.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A node entity.
   */
  protected function createContentEntity(RdfInterface $rdf_entity) {
    return $this->entityTypeManager()->getStorage('node')->create([
      'type' => $this->getBundle(),
      OgGroupAudienceHelperInterface::DEFAULT_FIELD => $rdf_entity->id(),
    ]);
  }

  /**
   * Returns the bundle of the entity this controller is about.
   *
   * @return string
   *   The bundle machine name.
   */
  abstract protected function getBundle(): string;

}
