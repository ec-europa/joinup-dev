<?php

declare(strict_types = 1);

namespace Drupal\easme_pledge\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\joinup_community_content\Controller\CommunityContentController;
use Drupal\rdf_entity\RdfInterface;

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

    return AccessResult::allowedIf($rdf_entity->bundle() === 'solution' && $account->isAuthenticated());
  }

}
