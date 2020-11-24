<?php

declare(strict_types = 1);

namespace Drupal\easme_pledge\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\joinup_community_content\Controller\CommunityContentController;
use Drupal\rdf_entity\RdfInterface;
use Drupal\node\NodeTypeInterface;

/**
 * Controller that handles the form to add pledge entities to a solution.
 *
 * The parent is passed as a parameter from the route.
 */
class PledgeController extends CommunityContentController {

  /**
   * {@inheritdoc}
   */
  protected function getBundle(): string {
    return 'pledge';
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
   */
  public function createAccess(RdfInterface $rdf_entity, NodeTypeInterface $node_type, AccountInterface $account): AccessResultInterface {
    return AccessResult::allowedIf($rdf_entity->bundle() === 'solution' && $account->isAuthenticated());
  }

}
