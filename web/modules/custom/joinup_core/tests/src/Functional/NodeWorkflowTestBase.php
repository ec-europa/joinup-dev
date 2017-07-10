<?php

namespace Drupal\Tests\joinup_core\Functional;

use Drupal\Core\Session\AnonymousUserSession;
use Drupal\joinup_core\ELibraryCreationOptions;
use Drupal\node\Entity\Node;
use Drupal\og\Entity\OgRole;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\RdfInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;

/**
 * Base setup for a Joinup workflow test for community content.
 *
 * @group rdf_entity
 */
abstract class NodeWorkflowTestBase extends JoinupWorkflowTestBase {

  const PRE_MODERATION = 1;
  const POST_MODERATION = 0;

  /**
   * A user assigned as an owner to document entities.
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
   * @var \Drupal\joinup_core\NodeWorkflowAccessControlHandler
   */
  protected $workflowAccess;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->workflowAccess = $this->container->get('joinup_core.node_workflow_access');
    $this->userOwner = $this->createUser();
    $this->userAnonymous = new AnonymousUserSession();
    $this->userAuthenticated = $this->createUser();
    $this->userModerator = $this->createUserWithRoles(['moderator']);
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
  public function testCrudAccess() {
    $this->createOperationTest();
    $this->readOperationTest();
    $this->updateOperationTest();
    $this->deleteOperationTest();
  }

  /**
   * Tests the 'create' operation access.
   */
  protected function createOperationTest() {
    $operation = 'create';
    $test_roles = array_diff($this->getAvailableUsers(), ['userOwner']);
    foreach ($this->createAccessProvider() as $parent_bundle => $moderation_data) {
      foreach ($moderation_data as $moderation => $elibrary_data) {
        foreach ($elibrary_data as $elibrary => $allowed_roles) {
          $parent = $this->createParent($parent_bundle, 'validated', $moderation, $elibrary);
          $content = Node::create([
            'title' => $this->randomMachineName(),
            'type' => $this->getEntityBundle(),
            OgGroupAudienceHelper::DEFAULT_FIELD => $parent->id(),
            'uid' => $this->userOwner->id(),
          ]);

          $non_allowed_roles = array_diff($test_roles, array_keys($allowed_roles));
          foreach ($allowed_roles as $user_var => $expected_transitions) {
            $message = "Parent bundle: {$parent_bundle}, Content bundle: {$this->getEntityBundle()}, Content state: -new entity-, Ownership: any, User variable: {$user_var}, Operation: {$operation}";
            $allowed_transitions = $this->workflowHelper->getAvailableTransitions($content, $this->{$user_var});
            $this->assertTransitionsEqual($expected_transitions, $allowed_transitions, $message);
          }
          foreach ($non_allowed_roles as $user_var) {
            $message = "Parent bundle: {$parent_bundle}, Content bundle: {$this->getEntityBundle()}, Content state: -new entity-, Ownership: any, User variable: {$user_var}, Operation: {$operation}";
            $access = $this->entityAccess->access($content, $operation, $this->{$user_var});
            $this->assertEquals(FALSE, $access, $message);
          }
        }
      }
    }
  }

  /**
   * Tests the 'view' (read) operation access.
   *
   * @todo: Add test for unpublished parent.
   */
  protected function readOperationTest() {
    $test_roles = array_diff($this->getAvailableUsers(), ['userOwner']);
    $operation = 'view';
    foreach ($this->viewAccessProvider() as $parent_bundle => $state_data) {
      $parent = $this->createParent($parent_bundle, 'validated');
      foreach ($state_data as $content_state => $ownership_data) {
        $content = Node::create([
          'title' => $this->randomMachineName(),
          'type' => $this->getEntityBundle(),
          OgGroupAudienceHelper::DEFAULT_FIELD => $parent->id(),
          'uid' => $this->userOwner->id(),
          'field_state' => $content_state,
          'status' => $this->isPublishedState($content_state),
        ]);
        $content->save();

        $expected_own_access = isset($ownership_data['own']) && $ownership_data['own'] === TRUE;
        $message = "Parent bundle: {$parent_bundle}, Content bundle: {$this->getEntityBundle()}, Content state: {$content_state}, Ownership: own, User variable: userOwner, Operation: {$operation}";
        $access = $this->entityAccess->access($content, $operation, $this->userOwner);
        $this->assertEquals($expected_own_access, $access, $message);

        $allowed_roles = $ownership_data['any'];
        $non_allowed_roles = array_diff($test_roles, $allowed_roles);
        foreach ($allowed_roles as $user_var) {
          $message = "Parent bundle: {$parent_bundle}, Content bundle: {$this->getEntityBundle()}, Content state: {$content_state}, Ownership: any, User variable: {$user_var}, Operation: {$operation}";
          $access = $this->entityAccess->access($content, $operation, $this->{$user_var});
          $this->assertEquals(TRUE, $access, $message);
        }
        foreach ($non_allowed_roles as $user_var) {
          $message = "Parent bundle: {$parent_bundle}, Content bundle: {$this->getEntityBundle()}, Content state: {$content_state}, Ownership: any, User variable: {$user_var}, Operation: {$operation}";
          $access = $this->entityAccess->access($content, $operation, $this->{$user_var});
          $this->assertEquals(FALSE, $access, $message);
        }
      }
    }
  }

  /**
   * Tests the 'update' operation access.
   */
  protected function updateOperationTest() {
    $test_roles = array_diff($this->getAvailableUsers(), ['userOwner']);
    $operation = 'update';
    foreach ($this->updateAccessProvider() as $parent_bundle => $moderation_data) {
      foreach ($moderation_data as $moderation => $state_data) {
        $parent = $this->createParent($parent_bundle, 'validated', $moderation);
        foreach ($state_data as $content_state => $ownership_data) {
          $content = Node::create([
            'title' => $this->randomMachineName(),
            'type' => $this->getEntityBundle(),
            OgGroupAudienceHelper::DEFAULT_FIELD => $parent->id(),
            'uid' => $this->userOwner->id(),
            'field_state' => $content_state,
            'status' => $this->isPublishedState($content_state),
          ]);
          $content->save();

          $own_access = isset($ownership_data['own']) && !empty($ownership_data['own']);
          if ($own_access) {
            $message = "Parent bundle: {$parent_bundle}, Content bundle: {$this->getEntityBundle()}, Content state: {$content_state}, Ownership: own, User variable: userOwner, Operation: {$operation}";
            $allowed_transitions = $this->workflowHelper->getAvailableTransitions($content, $this->userOwner);
            $expected_transitions = $ownership_data['own'];
            $this->assertTransitionsEqual($expected_transitions, $allowed_transitions, $message);
          }
          else {
            $message = "Parent bundle: {$parent_bundle}, Content bundle: {$this->getEntityBundle()}, Content state: {$content_state}, Ownership: own, User variable: userOwner, Operation: {$operation}";
            $access = $this->entityAccess->access($content, $operation, $this->userOwner);
            $this->assertEquals(FALSE, $access, $message);
          }

          $allowed_roles = array_keys($ownership_data['any']);
          $non_allowed_roles = array_diff($test_roles, $allowed_roles);
          foreach ($ownership_data['any'] as $user_var => $expected_transitions) {
            $message = "Parent bundle: {$parent_bundle}, Moderation: {$moderation}, Content bundle: {$this->getEntityBundle()}, Content state: {$content_state}, Ownership: any, User variable: {$user_var}, Operation: {$operation}";
            $allowed_transitions = $this->workflowHelper->getAvailableTransitions($content, $this->{$user_var});
            $this->assertTransitionsEqual($expected_transitions, $allowed_transitions, $message);
          }
          foreach ($non_allowed_roles as $user_var) {
            $access = $this->entityAccess->access($content, $operation, $this->{$user_var});
            $this->assertEquals(FALSE, $access);
          }
        }
      }
    }
  }

  /**
   * Tests the 'delete' operation access.
   */
  protected function deleteOperationTest() {
    $test_roles = array_diff($this->getAvailableUsers(), ['userOwner']);
    $operation = 'delete';
    foreach ($this->deleteAccessProvider() as $parent_bundle => $moderation_data) {
      foreach ($moderation_data as $moderation => $state_data) {
        $parent = $this->createParent($parent_bundle, 'validated', $moderation);
        foreach ($state_data as $content_state => $ownership_data) {
          $content = Node::create([
            'title' => $this->randomMachineName(),
            'type' => $this->getEntityBundle(),
            OgGroupAudienceHelper::DEFAULT_FIELD => $parent->id(),
            'uid' => $this->userOwner->id(),
            'field_state' => $content_state,
            'status' => $this->isPublishedState($content_state),
          ]);

          $expected_own_access = isset($ownership_data['own']) && $ownership_data['own'] === TRUE;
          $access = $this->entityAccess->access($content, $operation, $this->userOwner);
          $this->assertEquals($expected_own_access, $access);

          $allowed_roles = $ownership_data['any'];
          $non_allowed_roles = array_diff($test_roles, $allowed_roles);
          foreach ($allowed_roles as $user_var) {
            $access = $this->entityAccess->access($content, $operation, $this->{$user_var});
            $this->assertEquals(TRUE, $access);
          }
          foreach ($non_allowed_roles as $user_var) {
            $access = $this->entityAccess->access($content, $operation, $this->{$user_var});
            $this->assertEquals(FALSE, $access);
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
   *      'elibrary status' => [
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
   */
  protected function createAccessProvider() {
    return [
      'collection' => [
        self::PRE_MODERATION => [
          ELibraryCreationOptions::FACILITATORS => [
            'userModerator' => [
              'save_as_draft',
              'propose',
              'publish',
            ],
            'userOgFacilitator' => [
              'save_as_draft',
              'propose',
              'publish',
            ],
          ],
          ELibraryCreationOptions::MEMBERS => [
            'userModerator' => [
              'save_as_draft',
              'propose',
              'publish',
            ],
            'userOgMember' => [
              'save_as_draft',
              'propose',
            ],
            'userOgFacilitator' => [
              'save_as_draft',
              'propose',
              'publish',
            ],
          ],
          ELibraryCreationOptions::REGISTERED_USERS => [
            'userAuthenticated' => [
              'save_as_draft',
              'propose',
            ],
            'userModerator' => [
              'save_as_draft',
              'propose',
              'publish',
            ],
            'userOgAdministrator' => [
              'save_as_draft',
              'propose',
            ],
            'userOgMember' => [
              'save_as_draft',
              'propose',
            ],
            'userOgFacilitator' => [
              'save_as_draft',
              'propose',
              'publish',
            ],
          ],
        ],
        self::POST_MODERATION => [
          ELibraryCreationOptions::FACILITATORS => [
            'userModerator' => [
              'save_as_draft',
              'publish',
            ],
            'userOgFacilitator' => [
              'save_as_draft',
              'publish',
            ],
          ],
          ELibraryCreationOptions::MEMBERS => [
            'userModerator' => [
              'save_as_draft',
              'publish',
            ],
            'userOgMember' => [
              'save_as_draft',
              'publish',
            ],
            'userOgFacilitator' => [
              'save_as_draft',
              'publish',
            ],
          ],
          ELibraryCreationOptions::REGISTERED_USERS => [
            'userAuthenticated' => [
              'save_as_draft',
              'publish',
            ],
            'userModerator' => [
              'save_as_draft',
              'publish',
            ],
            'userOgAdministrator' => [
              'save_as_draft',
              'publish',
            ],
            'userOgMember' => [
              'save_as_draft',
              'publish',
            ],
            'userOgFacilitator' => [
              'save_as_draft',
              'publish',
            ],
          ],
        ],
      ],
      'solution' => [
        self::PRE_MODERATION => [
          ELibraryCreationOptions::FACILITATORS => [
            'userModerator' => [
              'save_as_draft',
              'propose',
              'publish',
            ],
            'userOgFacilitator' => [
              'save_as_draft',
              'propose',
              'publish',
            ],
          ],
          ELibraryCreationOptions::MEMBERS => [
            'userModerator' => [
              'save_as_draft',
              'propose',
              'publish',
            ],
            'userOgFacilitator' => [
              'save_as_draft',
              'propose',
              'publish',
            ],
          ],
          ELibraryCreationOptions::REGISTERED_USERS => [
            'userAuthenticated' => [
              'save_as_draft',
              'propose',
            ],
            'userModerator' => [
              'save_as_draft',
              'propose',
              'publish',
            ],
            'userOgAdministrator' => [
              'save_as_draft',
              'propose',
            ],
            'userOgFacilitator' => [
              'save_as_draft',
              'propose',
              'publish',
            ],
          ],
        ],
        self::POST_MODERATION => [
          ELibraryCreationOptions::FACILITATORS => [
            'userModerator' => [
              'save_as_draft',
              'publish',
            ],
            'userOgFacilitator' => [
              'save_as_draft',
              'publish',
            ],
          ],
          ELibraryCreationOptions::MEMBERS => [
            'userModerator' => [
              'save_as_draft',
              'publish',
            ],
            'userOgFacilitator' => [
              'save_as_draft',
              'publish',
            ],
          ],
          ELibraryCreationOptions::REGISTERED_USERS => [
            'userAuthenticated' => [
              'save_as_draft',
              'publish',
            ],
            'userModerator' => [
              'save_as_draft',
              'publish',
            ],
            'userOgAdministrator' => [
              'save_as_draft',
              'publish',
            ],
            'userOgFacilitator' => [
              'save_as_draft',
              'publish',
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
   */
  protected function viewAccessProvider() {
    return [
      'collection' => [
        'draft' => [
          'own' => TRUE,
          'any' => [],
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
          'any' => [],
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
   *      'state' => [
   *        'own' => [
   *          'transition allowed',
   *          'transition allowed',
   *        ],
   *        'any' => [
   *          'user variable' => [
   *            'transition allowed',
   *            'transition allowed',
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
   */
  protected function updateAccessProvider() {
    $data = [
      self::PRE_MODERATION => [
        'draft' => [
          'own' => [
            'save_as_draft',
            'propose',
          ],
          'any' => [],
        ],
        'proposed' => [
          'own' => [
            'update_proposed',
          ],
          'any' => [
            'userModerator' => [
              'update_proposed',
              'approve_proposed',
            ],
            'userOgFacilitator' => [
              'update_proposed',
              'approve_proposed',
            ],
          ],
        ],
        'validated' => [
          'own' => [
            'save_new_draft',
            'request_deletion',
          ],
          'any' => [
            'userModerator' => [
              'update_published',
              'save_new_draft',
              'request_changes',
              'report',
            ],
            'userOgFacilitator' => [
              'update_published',
              'save_new_draft',
              'request_changes',
              'report',
            ],
          ],
        ],
        'needs_update' => [
          'own' => [
            'propose_from_reported',
          ],
          'any' => [
            'userModerator' => [
              'propose_from_reported',
            ],
            'userOgFacilitator' => [
              'propose_from_reported',
            ],
          ],
        ],
        'deletion_request' => [
          'any' => [
            'userModerator' => [
              'reject_deletion',
            ],
            'userOgFacilitator' => [
              'reject_deletion',
            ],
          ],
        ],
      ],
      self::POST_MODERATION => [
        'draft' => [
          'own' => [
            'save_as_draft',
            'publish',
          ],
          'any' => [
            'userModerator' => [
              'save_as_draft',
              'publish',
            ],
            'userOgFacilitator' => [
              'save_as_draft',
              'publish',
            ],
          ],
        ],
        'proposed' => [
          'own' => [
            'update_proposed',
          ],
          'any' => [
            'userModerator' => [
              'update_proposed',
              'approve_proposed',
            ],
            'userOgFacilitator' => [
              'update_proposed',
              'approve_proposed',
            ],
          ],
        ],
        'validated' => [
          'own' => [
            'update_published',
            'save_new_draft',
          ],
          'any' => [
            'userModerator' => [
              'update_published',
              'save_new_draft',
              'request_changes',
              'report',
            ],
            'userOgFacilitator' => [
              'update_published',
              'save_new_draft',
              'request_changes',
              'report',
            ],
          ],
        ],
        'needs_update' => [
          'own' => [
            'propose_from_reported',
          ],
          'any' => [
            'userModerator' => [
              'propose_from_reported',
            ],
            'userOgFacilitator' => [
              'propose_from_reported',
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
   *        'own' => true|false,
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
   */
  protected function deleteAccessProvider() {
    return [
      'collection' => [
        self::PRE_MODERATION => [
          'draft' => [
            'own' => TRUE,
            'any' => [],
          ],
          'proposed' => [
            'any' => [
              'userModerator',
              'userOgFacilitator',
            ],
          ],
          'validated' => [
            'any' => [
              'userModerator',
              'userOgFacilitator',
            ],
          ],
          'needs_update' => [
            'any' => [
              'userModerator',
              'userOgFacilitator',
            ],
          ],
          'deletion_request' => [
            'any' => [
              'userModerator',
              'userOgFacilitator',
            ],
          ],
        ],
        self::POST_MODERATION => [
          'draft' => [
            'own' => TRUE,
            'any' => [],
          ],
          'proposed' => [
            'own' => TRUE,
            'any' => [
              'userModerator',
              'userOgFacilitator',
            ],
          ],
          'validated' => [
            'own' => TRUE,
            'any' => [
              'userModerator',
              'userOgFacilitator',
            ],
          ],
          'needs_update' => [
            'own' => TRUE,
            'any' => [
              'userModerator',
              'userOgFacilitator',
            ],
          ],
        ],
      ],
      'solution' => [
        self::PRE_MODERATION => [
          'draft' => [
            'own' => TRUE,
            'any' => [],
          ],
          'proposed' => [
            'any' => [
              'userModerator',
              'userOgFacilitator',
            ],
          ],
          'validated' => [
            'any' => [
              'userModerator',
              'userOgFacilitator',
            ],
          ],
          'needs_update' => [
            'any' => [
              'userModerator',
              'userOgFacilitator',
            ],
          ],
          'deletion_request' => [
            'any' => [
              'userModerator',
              'userOgFacilitator',
            ],
          ],
        ],
        self::POST_MODERATION => [
          'draft' => [
            'own' => TRUE,
            'any' => [],
          ],
          'proposed' => [
            'own' => TRUE,
            'any' => [
              'userModerator',
              'userOgFacilitator',
            ],
          ],
          'validated' => [
            'own' => TRUE,
            'any' => [
              'userModerator',
              'userOgFacilitator',
            ],
          ],
          'needs_update' => [
            'own' => TRUE,
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
   * @return array
   *   A list of user variables.
   */
  protected function getAvailableUsers() {
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
   * @param string $e_library
   *   The 'eLibrary_creation' value of the parent entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The created entity.
   */
  protected function createParent($bundle, $state = 'validated', $moderation = NULL, $e_library = NULL) {
    // Make sure the current user is set to anonymous when creating solutions
    // through the API so we can assign the administrator manually. If a user is
    // logged in during creation of the solution they will automatically become
    // the administrator.
    $this->setCurrentUser($this->userAnonymous);

    $field_identifier = [
      'collection' => 'field_ar_',
      'solution' => 'field_is_',
    ];

    $parent = Rdf::create([
      'label' => $this->randomMachineName(),
      'rid' => $bundle,
      $field_identifier[$bundle] . 'state' => $state,
      $field_identifier[$bundle] . 'moderation' => $moderation,
      $field_identifier[$bundle] . 'elibrary_creation' => $e_library === NULL ? ELibraryCreationOptions::REGISTERED_USERS : $e_library,
    ]);
    $parent->save();
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
  protected function getEntityType() {
    return 'node';
  }

  /**
   * Returns an array of the available eLibrary states.
   *
   * @return array
   *   An array of the available eLibrary states.
   */
  protected function getElibraryStates() {
    return [
      ELibraryCreationOptions::FACILITATORS,
      ELibraryCreationOptions::MEMBERS,
      ELibraryCreationOptions::REGISTERED_USERS,
    ];
  }

  /**
   * Asserts that two transition arrays are equal.
   *
   * @param array $expected
   *   The expected transitions as a list of Ids.
   * @param \Drupal\state_machine\Plugin\Workflow\WorkflowTransition[] $actual
   *   The actual transitions.
   * @param string $message
   *   A message to show to the assertion.
   */
  protected function assertTransitionsEqual(array $expected, array $actual, $message = '') {
    $actual = array_map(function (WorkflowTransition $transition) {
      return $transition->getId();
    }, $actual);
    $actual = array_values($actual);
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
  protected function isPublishedState($state) {
    return in_array($state, $this->getPublishedStates());
  }

  /**
   * Returns the published states.
   *
   * @return array
   *   An array of workflow states.
   */
  protected function getPublishedStates() {
    return ['validated'];
  }

}
