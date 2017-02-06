<?php

namespace Drupal\Tests\joinup_document\Functional;

use Drupal\Core\Session\AnonymousUserSession;
use Drupal\node\Entity\Node;
use Drupal\og\Entity\OgRole;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\RdfInterface;
use Drupal\Tests\joinup_core\JoinupWorkflowTestBase;

/**
 * Tests CRUD operations and workflow transitions for the document node.
 */
class DocumentWorkflowTest extends JoinupWorkflowTestBase {

  /**
   * Flag for pre-moderated groups.
   */
  const PRE_MODERATION = 1;

  /**
   * Flag for post-moderated groups.
   */
  const POST_MODERATION = 0;

  /**
   * Flag for option for the eLibrary creation field.
   *
   * Only facilitators are allowed to create content.
   */
  const ELIBRARY_ONLY_FACILITATORS = 0;

  /**
   * Flag for option for the eLibrary creation field.
   *
   * Only users that are subscribed to the group are allowed to create content.
   */
  const ELIBRARY_MEMBERS_FACILITATORS = 1;

  /**
   * Flag for option for the eLibrary creation field.
   *
   * All registered users can create content.
   */
  const ELIBRARY_REGISTERED_USERS = 2;

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
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

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
   * Since the browser test is a slow test, both create access and read/update/
   * delete access are tested below.
   */
  public function testCrudAccess() {
    // The owner, when it comes to 'create' operation, is just an authenticated
    // user.
    $test_roles = array_diff($this->getAvailableUsers(), ['userOwner']);

    // Test create access.
    foreach ($this->createAccessProvider() as $parent_bundle => $elibrary_data) {
      foreach ($elibrary_data as $elibrary => $allowed_roles) {
        $parent = $this->createParent($parent_bundle, 'validated', NULL, $elibrary);
        $content = Node::create([
          'type' => 'document',
          OgGroupAudienceHelper::DEFAULT_FIELD => $parent->id(),
          'uid' => $this->userOwner->id(),
        ]);

        $operation = 'create';
        foreach ($test_roles as $user_var) {
          $this->userProvider->setUser($this->{$user_var});
          $access = $this->workflowAccess->entityAccess($content, $operation, $this->{$user_var})->isAllowed();
          $expected = in_array($user_var, $allowed_roles);
          $message = "User {$user_var} should " . ($expected ? '' : 'not') . " have {$operation} access for bundle 'document' with a {$parent_bundle} parent with eLibrary: {$elibrary}.";
          $this->assertEquals($expected, $access, $message);
        }
      }
    }

    $test_roles = $this->getAvailableUsers();
    foreach ($this->readUpdateDeleteAccessProvider() as $parent_bundle => $parent_state_data) {
      foreach ($parent_state_data as $parent_state => $moderation_data) {
        foreach ($moderation_data as $moderation => $content_state_data) {
          $parent = $this->createParent($parent_bundle, $parent_state, $moderation);
          foreach ($content_state_data as $content_state => $operation_data) {
            foreach ($operation_data as $operation => $allowed_roles) {
              $content = $this->createNode([
                'type' => 'document',
                OgGroupAudienceHelper::DEFAULT_FIELD => $parent->id(),
                'uid' => $this->userOwner->id(),
                'field_state' => $content_state,
                'status' => $this->isPublishedState($content_state),
              ]);

              $moderated_message = $moderation ? 'pre moderated' : 'post moderated';
              foreach ($test_roles as $user_var) {
                $this->userProvider->setUser($this->{$user_var});
                $access = $this->workflowAccess->entityAccess($content, $operation, $this->{$user_var})->isAllowed();
                $expected = in_array($user_var, $allowed_roles);
                $message = "User {$user_var} should " . ($expected ? '' : 'not') . " have {$operation} access for the '{$content_state}' 'document' with a {$moderated_message} {$parent_bundle} parent in a {$parent_state} state.";
                $this->assertEquals($expected, $access, $message);
              }
            }
          }
        }
      }
    }
  }

  /**
   * Returns a list of users to be used for the tests.
   *
   * @return array
   *    A list of user variables.
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
   * Provides data for create access check.
   *
   * The access to create a release is checked against the parent entity as it
   * is dependant to og permissions.
   * The structure of the array is:
   * @code
   * $access_array = [
   *  'parent_bundle' => [
   *    'elibrary_status' => [
   *      'role_allowed'
   *      'role_allowed'
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
        self::ELIBRARY_ONLY_FACILITATORS => [
          'userModerator',
          'userOgFacilitator',
        ],
        self::ELIBRARY_MEMBERS_FACILITATORS => [
          'userModerator',
          'userOgMember',
          'userOgFacilitator',
          'userOgAdministrator',
        ],
        self::ELIBRARY_REGISTERED_USERS => [
          'userAuthenticated',
          'userModerator',
          'userOgMember',
          'userOgFacilitator',
          'userOgAdministrator',
        ],
      ],
      'solution' => [
        self::ELIBRARY_ONLY_FACILITATORS => [
          'userModerator',
          'userOgFacilitator',
        ],
        self::ELIBRARY_MEMBERS_FACILITATORS => [
          'userModerator',
          'userOgFacilitator',
        ],
        self::ELIBRARY_REGISTERED_USERS => [
          'userAuthenticated',
          'userModerator',
          'userOgFacilitator',
          // The following users also have access due to being authenticated.
          'userOgMember',
          'userOgAdministrator',
        ],
      ],
    ];
  }

  /**
   * Creates a parent entity and initializes memberships.
   *
   * @param string $bundle
   *   The bundle of the entity to create.
   * @param string $state
   *    The state of the entity.
   * @param string $moderation
   *    Whether the parent is pre or post moderated.
   * @param string $elibrary
   *    The 'eLibrary_creation' value of the parent entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *    The created entity.
   */
  protected function createParent($bundle, $state = 'validated', $moderation = NULL, $elibrary = NULL) {
    $field_prefix = $bundle === 'collection' ? 'field_ar_' : 'field_is_';

    $parent = Rdf::create([
      'label' => $this->randomMachineName(),
      'rid' => $bundle,
      $field_prefix . 'state' => $state,
      $field_prefix . 'moderation' => $moderation,
      $field_prefix . 'elibrary_creation' => $elibrary,
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
   * Provides data for access check.
   *
   * The structure of the array is:
   * @code
   * $access_array = [
   *   'parent_state' => [
   *     'entity_state' => [
   *        ['operation', 'user1', 'expected_result'],
   *        ['operation', 'user2', 'expected_result'],
   *     ],
   *   ],
   * ];
   * @code
   * Only two parent states need to be tested as the expected result might
   * differ depending on whether the parent is published or not.
   *
   * The reason that this is just an array and not a proper provider is that it
   * would take a lot of time to reinstall an instance of the site for each
   * entry.
   */
  protected function readUpdateDeleteAccessProvider() {
    $access_array = [
      'draft' => [
        self::PRE_MODERATION => [
          'draft' => [
            'view' => [
              'userOwner',
              'userModerator',
              'userOgFacilitator',
            ],
            'update' => [
              'userOwner',
              'userModerator',
              'userOgFacilitator',
            ],
            'delete' => [
              'userModerator',
              'userOgFacilitator',
            ],
          ],
          'proposed' => [
            'view' => [
              'userOwner',
              'userModerator',
              'userOgFacilitator',
            ],
            'update' => [
              'userOwner',
              'userModerator',
              'userOgFacilitator',
            ],
            'delete' => [
              'userModerator',
              'userOgFacilitator',
            ],
          ],
          'validated' => [
            'view' => [
              'userOwner',
              'userModerator',
              'userOgFacilitator',
            ],
            'update' => [
              'userOwner',
              'userModerator',
              'userOgFacilitator',
            ],
            'delete' => [
              'userModerator',
              'userOgFacilitator',
            ],
          ],
          'request_deletion' => [
            'view' => [
              'userOwner',
              'userModerator',
              'userOgFacilitator',
            ],
            'update' => [
              'userModerator',
              'userOgFacilitator',
            ],
            'delete' => [
              'userModerator',
              'userOgFacilitator',
            ],
          ],
          'in_assessment' => [
            'view' => [
              'userOwner',
              'userModerator',
              'userOgFacilitator',
            ],
            'update' => [
              'userModerator',
              'userOgFacilitator',
            ],
            'delete' => [
              'userModerator',
              'userOgFacilitator',
            ],
          ],
        ],
        self::POST_MODERATION => [
          'draft' => [
            'view' => [
              'userOwner',
              'userModerator',
              'userOgFacilitator',
            ],
            'update' => [
              'userOwner',
              'userModerator',
              'userOgFacilitator',
            ],
            'delete' => [
              'userOwner',
              'userModerator',
              'userOgFacilitator',
            ],
          ],
          'proposed' => [
            'view' => [
              'userOwner',
              'userModerator',
              'userOgFacilitator',
            ],
            'update' => [
              'userOwner',
              'userModerator',
              'userOgFacilitator',
            ],
            'delete' => [
              'userOwner',
              'userModerator',
              'userOgFacilitator',
            ],
          ],
          'validated' => [
            'view' => [
              'userOwner',
              'userModerator',
              'userOgFacilitator',
            ],
            'update' => [
              'userOwner',
              'userModerator',
              'userOgFacilitator',
            ],
            'delete' => [
              'userOwner',
              'userModerator',
              'userOgFacilitator',
            ],
          ],
          'in_assessment' => [
            'view' => [
              'userOwner',
              'userModerator',
              'userOgFacilitator',
            ],
            'update' => [
              'userModerator',
              'userOgFacilitator',
            ],
            'delete' => [
              'userOwner',
              'userModerator',
              'userOgFacilitator',
            ],
          ],
        ],
      ],
    ];

    // When it comes to documents, access check is the same regardless of the
    // bundle of the parent.
    $return_array = [];
    foreach (['collection', 'solution'] as $bundle) {
      $return_array[$bundle] = $access_array;
    }

    return $return_array;
  }

  /**
   * Determines if a state should be published in the document workflow.
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
    $states = [
      'validated',
      'in_assessment',
      'request_deletion',
    ];

    return in_array($state, $states);
  }

  /**
   * Tests the document workflow.
   */
  public function testWorkflow() {
    foreach ($this->workflowTransitionsProvider() as $parent_moderation => $content_data) {
      foreach ($content_data as $content_state => $workflow_data) {
        foreach (['collection', 'solution'] as $parent_bundle) {
          $parent = $this->createParent($parent_bundle, 'validated', $parent_moderation);

          foreach ($workflow_data as $user_var => $transitions) {
            $content = $this->createNode([
              'type' => 'document',
              'uid' => $this->userOwner->id(),
              'field_state' => $content_state,
              OgGroupAudienceHelper::DEFAULT_FIELD => $parent->id(),
              'status' => $this->isPublishedState($content_state),
            ]);

            // Override the user to be checked for the allowed transitions.
            $this->userProvider->setUser($this->{$user_var});
            $actual_transitions = $content->get('field_state')->first()->getTransitions();
            $actual_transitions = array_map(function ($transition) {
              return $transition->getId();
            }, $actual_transitions);
            sort($actual_transitions);
            sort($transitions);

            $moderated_message = $parent_moderation ? 'pre-moderated' : 'post-moderated';
            $this->assertEquals($transitions, $actual_transitions, "Transitions do not match for user $user_var, state $content_state and a $moderated_message $parent_bundle for a parent.");
          }
        }
      }
    }
  }

  /**
   * Provides data for transition checks.
   *
   * The structure of the array is:
   * @code
   * $workflow_array = [
   *   'parent_moderation' => [
   *     'entity_state' => [
   *       'user' => [
   *         'transition',
   *         'transition',
   *       ],
   *     ],
   *   ],
   * ];
   * @code
   * There can be multiple transitions that can lead to a specific state, so
   * the check is being done on allowed transitions.
   */
  protected function workflowTransitionsProvider() {
    return [
      self::PRE_MODERATION => [
        '__new__' => [
          'userAuthenticated' => [
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
            'validate',
          ],
          'userModerator' => [
            'save_as_draft',
            'propose',
            'validate',
          ],
        ],
        'draft' => [
          'userAuthenticated' => [],
          'userOwner' => [
            'propose',
          ],
          'userOgMember' => [],
          'userOgFacilitator' => [
            'validate',
          ],
          'userModerator' => [
            'validate',
          ],
        ],
        'proposed' => [
          'userAuthenticated' => [],
          'userOwner' => [
            'update_proposed',
          ],
          'userOgMember' => [],
          'userOgFacilitator' => [
            'update_proposed',
            'approve_proposed',
          ],
          'userModerator' => [
            'update_proposed',
            'approve_proposed',
          ],
        ],
        'validated' => [
          'userAuthenticated' => [],
          'userOwner' => [
            'request_deletion',
          ],
          'userOgMember' => [],
          'userOgFacilitator' => [
            'report',
            'update_published',
            'request_changes',
          ],
          'userModerator' => [
            'report',
            'update_published',
            'request_changes',
          ],
        ],
        'in_assessment' => [
          'userAuthenticated' => [],
          'userOwner' => [],
          'userOgMember' => [],
          'userOgFacilitator' => [
            'approve_report',
          ],
          'userModerator' => [
            'approve_report',
          ],
        ],
        'deletion_request' => [
          'userAuthenticated' => [],
          'userOwner' => [],
          'userOgMember' => [],
          'userOgFacilitator' => [
            'reject_deletion',
          ],
          'userModerator' => [
            'reject_deletion',
          ],
        ],
      ],
      self::POST_MODERATION => [
        '__new__' => [
          'userAuthenticated' => [
            'save_as_draft',
            'validate',
          ],
          'userOgMember' => [
            'save_as_draft',
            'validate',
          ],
          'userOgFacilitator' => [
            'save_as_draft',
            'validate',
          ],
          'userModerator' => [
            'save_as_draft',
            'validate',
          ],
        ],
        'draft' => [
          'userAuthenticated' => [],
          'userOwner' => [
            'validate',
          ],
          'userOgMember' => [],
          'userOgFacilitator' => [
            'validate',
          ],
          'userModerator' => [
            'validate',
          ],
        ],
        'proposed' => [
          'userAuthenticated' => [],
          'userOwner' => [
            'update_proposed',
          ],
          'userOgMember' => [],
          'userOgFacilitator' => [
            'update_proposed',
            'approve_proposed',
          ],
          'userModerator' => [
            'update_proposed',
            'approve_proposed',
          ],
        ],
        'validated' => [
          'userAuthenticated' => [],
          'userOwner' => [
            'update_published',
          ],
          'userOgMember' => [],
          'userOgFacilitator' => [
            'report',
            'update_published',
            'request_changes',
          ],
          'userModerator' => [
            'report',
            'update_published',
            'request_changes',
          ],
        ],
        'in_assessment' => [
          'userAuthenticated' => [],
          'userOwner' => [],
          'userOgMember' => [],
          'userOgFacilitator' => [
            'approve_report',
          ],
          'userModerator' => [
            'approve_report',
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityType() {
    return 'node';
  }

}
