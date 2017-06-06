<?php

namespace Drupal\joinup_document\Guard;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\joinup_core\Guard\NodeGuard;
use Drupal\joinup_core\JoinupRelationManager;
use Drupal\og\MembershipManagerInterface;

/**
 * Class JoinupDocumentFulfillmentGuard.
 */
class JoinupDocumentFulfillmentGuard extends NodeGuard {

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, JoinupRelationManager $relationManager, MembershipManagerInterface $ogMembershipManager, ConfigFactoryInterface $configFactory, AccountInterface $currentUser) {
    parent::__construct($entityTypeManager, $relationManager, $ogMembershipManager, $configFactory, $currentUser);
    $this->transitions = $this->configFactory->get('joinup_document.settings')->get('transitions');
  }

}
