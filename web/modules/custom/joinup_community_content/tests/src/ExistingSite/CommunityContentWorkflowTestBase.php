<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_community_content\ExistingSite;

use Drupal\Core\Session\AnonymousUserSession;
use Drupal\og\OgMembershipInterface;
use Drupal\Tests\joinup_workflow\ExistingSite\JoinupWorkflowExistingSiteTestBase;
use Drupal\joinup_group\ContentCreationOptions;
use Drupal\joinup_group\Entity\GroupInterface;
use Drupal\node\Entity\Node;
use Drupal\og\Entity\OgRole;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\rdf_entity\RdfInterface;
use weitzman\DrupalTestTraits\Entity\NodeCreationTrait;

/**
 * Base setup for a Joinup workflow test for community content.
 *
 * @group rdf_entity
 */
abstract class CommunityContentWorkflowTestBase extends JoinupWorkflowExistingSiteTestBase {

  use NodeCreationTrait;

  /**
   * The owner of the community content.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $userOwner;

  /**
   * A non authenticated user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $userAnonymous;

  /**
   * A user with the authenticated role.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $userAuthenticated;

  /**
   * A user with the moderator role.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $userModerator;

  /**
   * A user with the administrator role in the parent rdf entity.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $userOgAdministrator;

  /**
   * A user with the facilitator role in the parent rdf entity.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $userOgFacilitator;

  /**
   * A user with the member role in the parent rdf entity.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $userOgMember;

  /**
   * The workflow access provider service.
   *
   * This service is called in the corresponding entity access hooks but is
   * used directly for the create access since it requires an entity and not
   * just a bundle due to the need to check extra information regarding the
   * group that the entity belongs to.
   *
   * @var \Drupal\joinup_community_content\CommunityContentWorkflowAccessControlHandler
   */
  protected $workflowAccess;

  /**
   * The access control handler for Node entities.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $nodeAccessControlHandler;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->workflowAccess = $this->container->get('joinup_community_content.community_content_workflow_access');
    $this->nodeAccessControlHandler = $this->container->get('entity_type.manager')->getAccessControlHandler('node');
    $this->userOwner = $this->createUser();
    $this->userAnonymous = new AnonymousUserSession();
    $this->userAuthenticated = $this->createUser();
    $this->userModerator = $this->createUser([], NULL, FALSE, ['roles' => ['moderator']]);
    $this->userOgMember = $this->createUser();
    $this->userOgFacilitator = $this->createUser();
    $this->userOgAdministrator = $this->createUser();
  }

  /**
   * Tests the CRUD operations for the asset release entities.
   *
   * Since the browser test is a slow test, we test all CRUD operations in the
   * same test.
   */
  public function testCrudAccess(): void {
    $this->createOperationTest();
    $this->readOperationTest();
    $this->updateOperationTest();
    $this->deleteOperationTest();
  }

  /**
   * Tests the 'create' operation access.
   */
  protected function createOperationTest(): void {
    $operation = 'create';
    $test_roles = array_diff($this->getAvailableUsers(), ['userOwner']);
    foreach ($this->createAccessProvider() as $parent_bundle => $moderation_data) {
      foreach ($moderation_data as $moderation => $content_creation_data) {
        foreach ($content_creation_data as $content_creation => $allowed_roles) {
          $parent = $this->createParent($parent_bundle, 'validated', $moderation, $content_creation);
          $content = Node::create([
            'title' => $this->randomMachineName(),
            'type' => $this->getEntityBundle(),
            OgGroupAudienceHelper::DEFAULT_FIELD => $parent->id(),
            'uid' => $this->userOwner->id(),
          ]);

          $non_allowed_roles = array_diff($test_roles, array_keys($allowed_roles));
          foreach ($allowed_roles as $user_var => $expected_target_states) {
            $message = "Parent bundle: {$parent_bundle}, Content bundle: {$this->getEntityBundle()}, Content state: -new entity-, Ownership: any, Moderation: {$moderation}, Content creation: {$content_creation}, User variable: {$user_var}, Operation: {$operation}";
            $actual_target_states = $this->workflowHelper->getAvailableTargetStates($content, $this->{$user_var});
            $this->assertWorkflowStatesEqual($expected_target_states, $actual_target_states, $message);
          }
          foreach ($non_allowed_roles as $user_var) {
            $message = "Parent bundle: {$parent_bundle}, Content bundle: {$this->getEntityBundle()}, Content state: -new entity-, Ownership: any, Moderation: {$moderation}, Content creation: {$content_creation}, User variable: {$user_var}, Operation: {$operation}";
            $access = $this->entityAccess->access($content, $operation, $this->{$user_var});
            $this->assertFalse($access, $message);
          }
        }
      }
    }
  }

  /**
   * Tests the 'view' (read) operation access.
   *
   * @todo Add test for unpublished parent.
   */
  protected function readOperationTest() {
    $test_roles = array_diff($this->getAvailableUsers(), ['userOwner']);
    $operation = 'view';
    foreach ($this->viewAccessProvider() as $parent_bundle => $state_data) {
      $parent = $this->createParent($parent_bundle, 'validated');
      foreach ($state_data as $content_state => $ownership_data) {
        $content = $this->createNode([
          'title' => $this->randomMachineName(),
          'type' => $this->getEntityBundle(),
          OgGroupAudienceHelper::DEFAULT_FIELD => $parent->id(),
          'uid' => $this->userOwner->id(),
          'field_state' => $content_state,
          'status' => $this->isPublishedState($content_state),
        ]);

        $expected_own_access = isset($ownership_data['own']) && $ownership_data['own'] === TRUE;
        $message = "Parent bundle: {$parent_bundle}, Content bundle: {$this->getEntityBundle()}, Content state: {$content_state}, Ownership: own, User variable: userOwner, Operation: {$operation}";
        $access = $this->entityAccess->access($content, $operation, $this->userOwner);
        $this->assertEquals($expected_own_access, $access, $message);

        $allowed_roles = $ownership_data['any'];
        $non_allowed_roles = array_diff($test_roles, $allowed_roles);
        foreach ($allowed_roles as $user_var) {
          $message = "Parent bundle: {$parent_bundle}, Content bundle: {$this->getEntityBundle()}, Content state: {$content_state}, Ownership: any, User variable: {$user_var}, Operation: {$operation}";
          $access = $this->entityAccess->access($content, $operation, $this->{$user_var});
          $this->assertTrue($access, $message);
        }
        foreach ($non_allowed_roles as $user_var) {
          $message = "Parent bundle: {$parent_bundle}, Content bundle: {$this->getEntityBundle()}, Content state: {$content_state}, Ownership: any, User variable: {$user_var}, Operation: {$operation}";
          $access = $this->entityAccess->access($content, $operation, $this->{$user_var});
          $this->assertFalse($access, $message);
        }
      }
    }
  }

  /**
   * Tests the 'update' operation access.
   */
  protected function updateOperationTest(): void {
    $test_roles = array_diff($this->getAvailableUsers(), ['userOwner']);
    $operation = 'update';
    foreach ($this->updateAccessProvider() as $parent_bundle => $moderation_data) {
      foreach ($moderation_data as $moderation => $state_data) {
        $parent = $this->createParent($parent_bundle, 'validated', $moderation);
        foreach ($state_data as $content_state => $ownership_data) {
          $content = $this->createNode([
            'title' => $this->randomMachineName(),
            'type' => $this->getEntityBundle(),
            OgGroupAudienceHelper::DEFAULT_FIELD => $parent->id(),
            'uid' => $this->userOwner->id(),
            'field_state' => $content_state,
            'status' => $this->isPublishedState($content_state),
          ]);

          $own_access = isset($ownership_data['own']) && !empty($ownership_data['own']);
          if ($own_access) {
            $message = "Parent bundle: {$parent_bundle}, Content bundle: {$this->getEntityBundle()}, Content state: {$content_state}, Ownership: own, User variable: userOwner, Operation: {$operation}";
            $allowed_target_states = $this->workflowHelper->getAvailableTargetStates($content, $this->userOwner);
            $expected_target_states = $ownership_data['own'];
            $this->assertWorkflowStatesEqual($expected_target_states, $allowed_target_states, $message);
          }
          else {
            $message = "Parent bundle: {$parent_bundle}, Content bundle: {$this->getEntityBundle()}, Content state: {$content_state}, Ownership: own, User variable: userOwner, Operation: {$operation}";
            $access = $this->entityAccess->access($content, $operation, $this->userOwner);
            $this->assertFalse($access, $message);
          }

          $allowed_roles = array_keys($ownership_data['any']);
          $non_allowed_roles = array_diff($test_roles, $allowed_roles);
          foreach ($ownership_data['any'] as $user_var => $expected_target_states) {
            $message = "Parent bundle: {$parent_bundle}, Moderation: {$moderation}, Content bundle: {$this->getEntityBundle()}, Content state: {$content_state}, Ownership: any, User variable: {$user_var}, Operation: {$operation}";
            $allowed_target_states = $this->workflowHelper->getAvailableTargetStates($content, $this->{$user_var});
            $this->assertWorkflowStatesEqual($expected_target_states, $allowed_target_states, $message);
          }
          foreach ($non_allowed_roles as $user_var) {
            $access = $this->entityAccess->access($content, $operation, $this->{$user_var});
            $this->assertFalse($access);
          }
        }
      }
    }
  }

  /**
   * Tests the 'delete' operation access.
   */
  protected function deleteOperationTest(): void {
    $test_roles = array_diff($this->getAvailableUsers(), ['userOwner']);
    $operation = 'delete';
    foreach ($this->deleteAccessProvider() as $parent_bundle => $moderation_data) {
      foreach ($moderation_data as $moderation => $state_data) {
        $parent = $this->createParent($parent_bundle, 'validated', $moderation);
        foreach ($state_data as $content_state => $ownership_data) {
          $content = $this->createNode([
            'title' => $this->randomMachineName(),
            'type' => $this->getEntityBundle(),
            OgGroupAudienceHelper::DEFAULT_FIELD => $parent->id(),
            'uid' => $this->userOwner->id(),
            'field_state' => $content_state,
            'status' => $this->isPublishedState($content_state),
          ]);

          // A content author who is an active member of the group can delete
          // their own content if the workflow allows it.
          // @see CommunityContentWorkflowAccessControlHandler::entityDeleteAccess()
          $membership = $this->ogMembershipManager->createMembership($parent, $this->userOwner);
          $membership->save();
          // Entity access has a static cache which we must reset manually.
          $this->nodeAccessControlHandler->resetCache();
          $access = $this->entityAccess->access($content, $operation, $this->userOwner);
          $this->assertEquals($ownership_data['own']['member'], $access, $content_state);

          // Check a content author who has been blocked by a facilitator.
          $membership->setState(OgMembershipInterface::STATE_BLOCKED)->save();
          $this->nodeAccessControlHandler->resetCache();
          $access = $this->entityAccess->access($content, $operation, $this->userOwner);
          $this->assertEquals($ownership_data['own']['blocked'], $access);

          // Check a content author who is no longer a member of the group. They
          // might have left or been removed by a facilitator.
          $membership->delete();
          $this->nodeAccessControlHandler->resetCache();
          $access = $this->entityAccess->access($content, $operation, $this->userOwner);
          $this->assertEquals($ownership_data['own']['non-member'], $access);

          $allowed_roles = $ownership_data['any'];
          $non_allowed_roles = array_diff($test_roles, $allowed_roles);
          foreach ($allowed_roles as $user_var) {
            $message = "Parent bundle: {$parent_bundle}, Moderation: {$moderation}, Content bundle: {$this->getEntityBundle()}, Content state: {$content_state}, Ownership: any, User variable: {$user_var}, Operation: {$operation}";
            $access = $this->entityAccess->access($content, $operation, $this->{$user_var});
            $this->assertTrue($access, $message);
          }
          foreach ($non_allowed_roles as $user_var) {
            $message = "Parent bundle: {$parent_bundle}, Moderation: {$moderation}, Content bundle: {$this->getEntityBundle()}, Content state: {$content_state}, Ownership: any, User variable: {$user_var}, Operation: {$operation}";
            $access = $this->entityAccess->access($content, $operation, $this->{$user_var});
            $this->assertFalse($access, $message);
          }
        }
      }
    }
  }

  /**
   * Provides data for create access check.
   *
   * The access to create a release is checked against the parent entity as it
   * is dependant to og permissions.
   * The structure of the array is:
   * @code
   * $access_array = [
   *  'parent bundle' => [
   *    'moderation' => [
   *      'content creation status' => [
   *        'user variable' => [
   *          'transition allowed',
   *          'transition allowed',
   *         ],
   *       ],
   *     ],
   *   ],
   * ];
   * @code
   * The user variable represents the variable defined in the test.
   * No parent state needs to be checked as it does not affect the possibility
   * to create document.
   *
   * @return array
   *   Test cases.
   */
  protected function createAccessProvider(): array {
    return [
      'collection' => [
        GroupInterface::PRE_MODERATION => [
          ContentCreationOptions::FACILITATORS_AND_AUTHORS => [
            'userModerator' => [
              'draft',
              'proposed',
              'validated',
            ],
            'userOgFacilitator' => [
              'draft',
              'proposed',
              'validated',
            ],
          ],
          ContentCreationOptions::MEMBERS => [
            'userModerator' => [
              'draft',
              'proposed',
              'validated',
            ],
            'userOgMember' => [
              'draft',
              'proposed',
            ],
            'userOgFacilitator' => [
              'draft',
              'proposed',
              'validated',
            ],
            'userOgAdministrator' => [
              'draft',
              'proposed',
            ],
          ],
          ContentCreationOptions::REGISTERED_USERS => [
            'userAuthenticated' => [
              'draft',
              'proposed',
            ],
            'userModerator' => [
              'draft',
              'proposed',
              'validated',
            ],
            'userOgAdministrator' => [
              'draft',
              'proposed',
            ],
            'userOgMember' => [
              'draft',
              'proposed',
            ],
            'userOgFacilitator' => [
              'draft',
              'proposed',
              'validated',
            ],
          ],
        ],
        GroupInterface::POST_MODERATION => [
          ContentCreationOptions::FACILITATORS_AND_AUTHORS => [
            'userModerator' => [
              'draft',
              'validated',
            ],
            'userOgFacilitator' => [
              'draft',
              'validated',
            ],
          ],
          ContentCreationOptions::MEMBERS => [
            'userModerator' => [
              'draft',
              'validated',
            ],
            'userOgMember' => [
              'draft',
              'validated',
            ],
            'userOgFacilitator' => [
              'draft',
              'validated',
            ],
            'userOgAdministrator' => [
              'draft',
              'validated',
            ],
          ],
          ContentCreationOptions::REGISTERED_USERS => [
            'userAuthenticated' => [
              'draft',
              'validated',
            ],
            'userModerator' => [
              'draft',
              'validated',
            ],
            'userOgAdministrator' => [
              'draft',
              'validated',
            ],
            'userOgMember' => [
              'draft',
              'validated',
            ],
            'userOgFacilitator' => [
              'draft',
              'validated',
            ],
          ],
        ],
      ],
      'solution' => [
        GroupInterface::PRE_MODERATION => [
          ContentCreationOptions::FACILITATORS_AND_AUTHORS => [
            'userModerator' => [
              'draft',
              'proposed',
              'validated',
            ],
            'userOgFacilitator' => [
              'draft',
              'proposed',
              'validated',
            ],
          ],
          ContentCreationOptions::REGISTERED_USERS => [
            'userAuthenticated' => [
              'draft',
              'proposed',
            ],
            'userModerator' => [
              'draft',
              'proposed',
              'validated',
            ],
            'userOgAdministrator' => [
              'draft',
              'proposed',
            ],
            'userOgFacilitator' => [
              'draft',
              'proposed',
              'validated',
            ],
            'userOgMember' => [
              'draft',
              'proposed',
            ],
          ],
        ],
        GroupInterface::POST_MODERATION => [
          ContentCreationOptions::FACILITATORS_AND_AUTHORS => [
            'userModerator' => [
              'draft',
              'validated',
            ],
            'userOgFacilitator' => [
              'draft',
              'validated',
            ],
          ],
          ContentCreationOptions::REGISTERED_USERS => [
            'userAuthenticated' => [
              'draft',
              'validated',
            ],
            'userModerator' => [
              'draft',
              'validated',
            ],
            'userOgAdministrator' => [
              'draft',
              'validated',
            ],
            'userOgFacilitator' => [
              'draft',
              'validated',
            ],
            'userOgMember' => [
              'draft',
              'validated',
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Provides data for view access check.
   *
   * The access to view an entity is checked against the parent entity as it
   * is dependant to og permissions.
   * The structure of the array is:
   * @code
   * $access_array = [
   *  'parent bundle' => [
   *    'state' => [
   *      'own' => true|false,
   *      'any' => [
   *        'user variable',
   *        'user variable',
   *       ],
   *     ],
   *   ],
   * ];
   * @code
   * The user variable represents the variable defined in the test.
   * No parent state needs to be checked as it does not affect the possibility
   * to create document.
   *
   * @return array
   *   Test cases.
   */
  protected function viewAccessProvider(): array {
    return [
      'collection' => [
        'draft' => [
          'own' => TRUE,
          'any' => [
            'userModerator',
          ],
        ],
        'validated' => [
          'own' => TRUE,
          'any' => [
            'userAnonymous',
            'userAuthenticated',
            'userModerator',
            'userOgMember',
            'userOgFacilitator',
            'userOgAdministrator',
          ],
        ],
        'needs_update' => [
          'own' => TRUE,
          'any' => [
            'userModerator',
            'userOgFacilitator',
          ],
        ],
        'proposed' => [
          'own' => TRUE,
          'any' => [
            'userModerator',
            'userOgFacilitator',
          ],
        ],
        'deletion_request' => [
          'own' => TRUE,
          'any' => [
            'userModerator',
            'userOgFacilitator',
          ],
        ],
      ],
      'solution' => [
        'draft' => [
          'own' => TRUE,
          'any' => [
            'userModerator',
          ],
        ],
        'validated' => [
          'own' => TRUE,
          'any' => [
            'userAnonymous',
            'userAuthenticated',
            'userModerator',
            'userOgMember',
            'userOgFacilitator',
            'userOgAdministrator',
          ],
        ],
        'needs_update' => [
          'own' => TRUE,
          'any' => [
            'userModerator',
            'userOgFacilitator',
          ],
        ],
        'proposed' => [
          'own' => TRUE,
          'any' => [
            'userModerator',
            'userOgFacilitator',
          ],
        ],
        'deletion_request' => [
          'own' => TRUE,
          'any' => [
            'userModerator',
            'userOgFacilitator',
          ],
        ],
      ],
    ];
  }

  /**
   * Provides data for update access check.
   *
   * The access to update an entity is checked against the parent entity as it
   * is dependant to og permissions.
   * The structure of the array is:
   * @code
   * $access_array = [
   *  'parent bundle' => [
   *    'moderation' => [
   *      'from state' => [
   *        'own' => [
   *          'allowed to state',
   *          'allowed to state',
   *        ],
   *        'any' => [
   *          'user variable' => [
   *            'allowed to state',
   *            'allowed to state',
   *           ],
   *         ],
   *       ],
   *     ],
   *   ],
   * ];
   * @code
   * The user variable represents the variable defined in the test.
   * No parent state needs to be checked as it does not affect the possibility
   * to create document.
   *
   * @return array
   *   Test cases.
   */
  protected function updateAccessProvider(): array {
    $data = [
      GroupInterface::PRE_MODERATION => [
        'draft' => [
          'own' => [
            'draft',
            'proposed',
          ],
          'any' => [
            'userModerator' => [
              'draft',
              'proposed',
              'validated',
            ],
          ],
        ],
        'proposed' => [
          'own' => [
            'proposed',
          ],
          'any' => [
            'userModerator' => [
              'proposed',
              'validated',
            ],
            'userOgFacilitator' => [
              'proposed',
              'validated',
            ],
          ],
        ],
        'validated' => [
          'own' => [
            'draft',
            'deletion_request',
            'proposed',
          ],
          'any' => [
            'userModerator' => [
              'draft',
              'proposed',
              'needs_update',
              'validated',
            ],
            'userOgFacilitator' => [
              'draft',
              'proposed',
              'needs_update',
              'validated',
            ],
          ],
        ],
        'needs_update' => [
          'own' => [
            'proposed',
          ],
          'any' => [
            'userModerator' => [
              'proposed',
            ],
            'userOgFacilitator' => [
              'proposed',
            ],
          ],
        ],
        'deletion_request' => [
          'any' => [
            'userModerator' => [
              'validated',
            ],
            'userOgFacilitator' => [
              'validated',
            ],
          ],
        ],
      ],
      GroupInterface::POST_MODERATION => [
        'draft' => [
          'own' => [
            'draft',
            'validated',
          ],
          'any' => [
            'userModerator' => [
              'draft',
              'validated',
            ],
            'userOgFacilitator' => [
              'draft',
              'validated',
            ],
          ],
        ],
        'proposed' => [
          'own' => [
            'proposed',
          ],
          'any' => [
            'userModerator' => [
              'proposed',
              'validated',
            ],
            'userOgFacilitator' => [
              'proposed',
              'validated',
            ],
          ],
        ],
        'validated' => [
          'own' => [
            'draft',
            'validated',
          ],
          'any' => [
            'userModerator' => [
              'draft',
              'proposed',
              'needs_update',
              'validated',
            ],
            'userOgFacilitator' => [
              'draft',
              'proposed',
              'needs_update',
              'validated',
            ],
          ],
        ],
        'needs_update' => [
          'own' => [
            'proposed',
          ],
          'any' => [
            'userModerator' => [
              'proposed',
            ],
            'userOgFacilitator' => [
              'proposed',
            ],
          ],
        ],
      ],
    ];
    $return = [];
    foreach (['collection', 'solution'] as $bundle) {
      $return[$bundle] = $data;
    }

    return $return;
  }

  /**
   * Provides data for delete access check.
   *
   * The access to delete an entity is checked against the parent entity as it
   * is dependant to og permissions.
   * The structure of the array is:
   * @code
   * $access_array = [
   *  'parent bundle' => [
   *    'moderation' => [
   *      'state' => [
   *        'own' => [
   *          'membership status' => true|false,
   *        ],
   *        'any' => [
   *           'user variable',
   *           'user variable',
   *         ],
   *       ],
   *     ],
   *   ],
   * ];
   * @code
   * The user variable represents the variable defined in the test.
   * No parent state needs to be checked as it does not affect the possibility
   * to create document.
   *
   * The membership status represents the status of the owner within the group
   * that hosts the content. Can be 'member', 'blocked' or 'non-member'.
   *
   * @return array
   *   Test cases.
   */
  protected function deleteAccessProvider(): array {
    return [
      'collection' => [
        GroupInterface::PRE_MODERATION => [
          'draft' => [
            'own' => [
              'member' => TRUE,
              'blocked' => FALSE,
              'non-member' => FALSE,
            ],
            'any' => [
              'userModerator',
            ],
          ],
          'proposed' => [
            'own' => [
              'member' => FALSE,
              'blocked' => FALSE,
              'non-member' => FALSE,
            ],
            'any' => [
              'userModerator',
              'userOgFacilitator',
            ],
          ],
          'validated' => [
            'own' => [
              'member' => FALSE,
              'blocked' => FALSE,
              'non-member' => FALSE,
            ],
            'any' => [
              'userModerator',
              'userOgFacilitator',
            ],
          ],
          'needs_update' => [
            'own' => [
              'member' => FALSE,
              'blocked' => FALSE,
              'non-member' => FALSE,
            ],
            'any' => [
              'userModerator',
              'userOgFacilitator',
            ],
          ],
          'deletion_request' => [
            'own' => [
              'member' => FALSE,
              'blocked' => FALSE,
              'non-member' => FALSE,
            ],
            'any' => [
              'userModerator',
              'userOgFacilitator',
            ],
          ],
        ],
        GroupInterface::POST_MODERATION => [
          'draft' => [
            'own' => [
              'member' => TRUE,
              'blocked' => FALSE,
              'non-member' => FALSE,
            ],
            'any' => [
              'userModerator',
            ],
          ],
          'proposed' => [
            'own' => [
              'member' => TRUE,
              'blocked' => FALSE,
              'non-member' => FALSE,
            ],
            'any' => [
              'userModerator',
              'userOgFacilitator',
            ],
          ],
          'validated' => [
            'own' => [
              'member' => TRUE,
              'blocked' => FALSE,
              'non-member' => FALSE,
            ],
            'any' => [
              'userModerator',
              'userOgFacilitator',
            ],
          ],
          'needs_update' => [
            'own' => [
              'member' => TRUE,
              'blocked' => FALSE,
              'non-member' => FALSE,
            ],
            'any' => [
              'userModerator',
              'userOgFacilitator',
            ],
          ],
        ],
      ],
      'solution' => [
        GroupInterface::PRE_MODERATION => [
          'draft' => [
            'own' => [
              'member' => TRUE,
              'blocked' => FALSE,
              'non-member' => FALSE,
            ],
            'any' => [
              'userModerator',
            ],
          ],
          'proposed' => [
            'own' => [
              'member' => FALSE,
              'blocked' => FALSE,
              'non-member' => FALSE,
            ],
            'any' => [
              'userModerator',
              'userOgFacilitator',
            ],
          ],
          'validated' => [
            'own' => [
              'member' => FALSE,
              'blocked' => FALSE,
              'non-member' => FALSE,
            ],
            'any' => [
              'userModerator',
              'userOgFacilitator',
            ],
          ],
          'needs_update' => [
            'own' => [
              'member' => FALSE,
              'blocked' => FALSE,
              'non-member' => FALSE,
            ],
            'any' => [
              'userModerator',
              'userOgFacilitator',
            ],
          ],
          'deletion_request' => [
            'own' => [
              'member' => FALSE,
              'blocked' => FALSE,
              'non-member' => FALSE,
            ],
            'any' => [
              'userModerator',
              'userOgFacilitator',
            ],
          ],
        ],
        GroupInterface::POST_MODERATION => [
          'draft' => [
            'own' => [
              'member' => TRUE,
              'blocked' => FALSE,
              'non-member' => FALSE,
            ],
            'any' => [
              'userModerator',
            ],
          ],
          'proposed' => [
            'own' => [
              'member' => TRUE,
              'blocked' => FALSE,
              'non-member' => FALSE,
            ],
            'any' => [
              'userModerator',
              'userOgFacilitator',
            ],
          ],
          'validated' => [
            'own' => [
              'member' => TRUE,
              'blocked' => FALSE,
              'non-member' => FALSE,
            ],
            'any' => [
              'userModerator',
              'userOgFacilitator',
            ],
          ],
          'needs_update' => [
            'own' => [
              'member' => TRUE,
              'blocked' => FALSE,
              'non-member' => FALSE,
            ],
            'any' => [
              'userModerator',
              'userOgFacilitator',
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Returns a list of users to be used for the tests.
   *
   * @return string[]
   *   A list of user variables.
   */
  protected function getAvailableUsers(): array {
    return [
      'userOwner',
      'userAnonymous',
      'userAuthenticated',
      'userModerator',
      'userOgMember',
      'userOgFacilitator',
      'userOgAdministrator',
    ];
  }

  /**
   * Creates a parent entity and initializes memberships.
   *
   * @param string $bundle
   *   The bundle of the entity to create.
   * @param string $state
   *   The state of the entity.
   * @param string $moderation
   *   Whether the parent is pre or post moderated.
   * @param string $content_creation
   *   The content creation value of the parent entity.
   *
   * @return \Drupal\rdf_entity\RdfInterface
   *   The created parent entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   If entity creation fails.
   */
  protected function createParent($bundle, $state = 'validated', $moderation = NULL, $content_creation = NULL): RdfInterface {
    // Make sure the current user is set to anonymous when creating groups
    // through the API so we can assign the administrator manually. If a user is
    // logged in during creation of the group they will automatically become the
    // administrator.
    $this->setCurrentUser($this->userAnonymous);

    $field_identifier = [
      'collection' => 'field_ar_',
      'solution' => 'field_is_',
    ];

    $values = [
      'label' => $this->randomMachineName(),
      'rid' => $bundle,
      $field_identifier[$bundle] . 'state' => $state,
      $field_identifier[$bundle] . 'moderation' => $moderation,
      $field_identifier[$bundle] . 'content_creation' => $content_creation === NULL ? ContentCreationOptions::REGISTERED_USERS : $content_creation,
    ];

    // It's not possible to create orphan solutions.
    if ($bundle === 'solution') {
      $values['collection'] = $this->createRdfEntity([
        'rid' => 'collection',
        'label' => $this->randomString(),
        'field_ar_state' => 'validated',
      ]);
    }

    $parent = $this->createRdfEntity($values);
    $this->assertInstanceOf(RdfInterface::class, $parent, "The $bundle group was created.");

    $member_role = OgRole::getRole('rdf_entity', $bundle, 'member');
    $facilitator_role = OgRole::getRole('rdf_entity', $bundle, 'facilitator');
    $administrator_role = OgRole::getRole('rdf_entity', $bundle, 'administrator');
    $this->createOgMembership($parent, $this->userOgMember, [$member_role]);
    $this->createOgMembership($parent, $this->userOgFacilitator, [$facilitator_role]);
    $this->createOgMembership($parent, $this->userOgAdministrator, [$administrator_role]);

    return $parent;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityType(): string {
    return 'node';
  }

  /**
   * Asserts that two workflow state arrays are equal.
   *
   * @param string[] $expected
   *   The expected workflow states.
   * @param string[] $actual
   *   The actual workflow states.
   * @param string $message
   *   A message to show to the assertion.
   */
  protected function assertWorkflowStatesEqual(array $expected, array $actual, $message = ''): void {
    sort($actual);
    sort($expected);

    $this->assertEquals($expected, $actual, $message);
  }

  /**
   * Determines if a state should be published in the node workflow.
   *
   * When programmatically creating a node in a certain state, there is no
   * state_machine transition fired. The state_machine_revisions subscriber has
   * the code to handle publishing of states, but it won't kick in. This
   * function is used to determine if the node should be created as published.
   *
   * @param string $state
   *   The state to check.
   *
   * @return bool
   *   If the state is published or not.
   */
  protected function isPublishedState($state): bool {
    return in_array($state, $this->getPublishedStates());
  }

  /**
   * Returns the published states.
   *
   * @return string[]
   *   An array of workflow states.
   */
  protected function getPublishedStates(): array {
    return ['validated', 'archived'];
  }

}
