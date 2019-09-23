<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_core\ExistingSite;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\rdf_entity\Traits\DrupalTestTraits\RdfEntityCreationTrait;
use weitzman\DrupalTestTraits\Entity\UserCreationTrait;

/**
 * Provides an abstract class for workflow tests.
 */
abstract class JoinupWorkflowExistingSiteTestBase extends JoinupExistingSiteTestBase {

  use StringTranslationTrait;
  use UserCreationTrait;
  use RdfEntityCreationTrait;

  /**
   * The OG membership access manager service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * The OG membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $ogMembershipManager;

  /**
   * The entity access manager service.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $entityAccess;

  /**
   * The workflow helper service.
   *
   * @var \Drupal\joinup_core\WorkflowHelperInterface
   */
  protected $workflowHelper;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->ogMembershipManager = \Drupal::service('og.membership_manager');
    $this->ogAccess = \Drupal::service('og.access');
    $this->entityAccess = \Drupal::service('entity_type.manager')
      ->getAccessControlHandler($this->getEntityType());
    $this->workflowHelper = \Drupal::service('joinup_core.workflow.helper');
  }

  /**
   * Returns the type of the entity being tested.
   *
   * @return string
   *   The entity type.
   */
  abstract protected function getEntityType(): string;

  /**
   * Creates and asserts an OG membership.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The Og group.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user this membership refers to.
   * @param array $roles
   *   An array of role objects.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   If the membership entity cannot be created.
   */
  protected function createOgMembership(EntityInterface $group, AccountInterface $user, array $roles = []): void {
    $membership = $this->ogMembershipManager->createMembership($group, $user)
      ->setRoles($roles);
    $membership->save();
    // @todo Should we propose support for Drupal Test Traits in OG project?
    $this->markEntityForCleanup($membership);
  }

  /**
   * Returns the entity bundle for the tested node type.
   *
   * @return string
   *   The entity bundle machine name.
   */
  abstract protected function getEntityBundle(): string;

}
