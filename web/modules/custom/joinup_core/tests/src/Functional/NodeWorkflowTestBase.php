<?php

namespace Drupal\Tests\joinup_core\Functional;

use Drupal\Core\Session\AnonymousUserSession;
use Drupal\joinup_core\ELibraryCreationOptions;
use Drupal\node\Entity\Node;
use Drupal\og\Entity\OgRole;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\RdfInterface;

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
          'type' => $this->getEntityBundle(),
          OgGroupAudienceHelper::DEFAULT_FIELD => $parent->id(),
          'uid' => $this->userOwner->id(),
        ]);

        $non_allowed_roles = array_diff($test_roles, $allowed_roles);
        $operation = 'create';
        foreach ($allowed_roles as $user_var) {
          $access = $this->workflowAccess->entityAccess($content, $operation, $this->{$user_var})->isAllowed();
          $message = "User {$user_var} should have {$operation} access for bundle 'document' with a {$parent_bundle} parent with eLibrary: {$elibrary}.";
          $this->assertEquals(TRUE, $access, $message);
        }
        foreach ($non_allowed_roles as $user_var) {
          $access = $this->workflowAccess->entityAccess($content, 'create', $this->{$user_var})->isAllowed();
          $message = "User {$user_var} should not have {$operation} access for bundle 'document' with a {$parent_bundle} parent with eLibrary: {$elibrary}.";
          $this->assertEquals(FALSE, $access, $message);
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
                'type' => $this->getEntityBundle(),
                OgGroupAudienceHelper::DEFAULT_FIELD => $parent->id(),
                'uid' => $this->userOwner->id(),
                'field_state' => $content_state,
                'status' => $this->isPublishedState($content_state),
              ]);

              $non_allowed_roles = array_diff($test_roles, $allowed_roles);
              $moderated_message = $moderation ? 'pre moderated' : 'post moderated';
              foreach ($allowed_roles as $user_var) {
                $access = $this->entityAccess->access($content, $operation, $this->{$user_var});
                $message = "User {$user_var} should have {$operation} access for the '{$content_state}' 'document' with a {$moderated_message} {$parent_bundle} parent in a {$parent_state} state.";
                $this->assertEquals(TRUE, $access, $message);
              }
              foreach ($non_allowed_roles as $user_var) {
                $access = $this->entityAccess->access($content, $operation, $this->{$user_var});
                $message = "User {$user_var} should not have {$operation} access for the '{$content_state}' 'document' with a {$moderated_message} {$parent_bundle} parent in a {$parent_state} state.";
                $this->assertEquals(FALSE, $access, $message);
              }
            }
          }
        }
      }
    }
  }

  /**
   * Tests the document workflow.
   */
  public function testWorkflow() {
    foreach ($this->workflowTransitionsProvider() as $parent_bundle => $e_library_data) {
      foreach ($e_library_data as $e_library_state => $moderation_state_data) {
        foreach ($moderation_state_data as $parent_moderation => $content_data) {
          $parent = $this->createParent($parent_bundle, 'validated', $parent_moderation, $e_library_state);
          foreach ($content_data as $content_state => $workflow_data) {
            foreach ($workflow_data as $user_var => $transitions) {
              $content = $this->createNode([
                'type' => $this->getEntityBundle(),
                'uid' => $this->userOwner->id(),
                'field_state' => $content_state,
                OgGroupAudienceHelper::DEFAULT_FIELD => $parent->id(),
                'status' => $this->isPublishedState($content_state),
              ]);

              // Override the user to be checked for the allowed transitions.
              $actual_transitions = $this->workflowHelper->getAvailableTransitions($content, $this->{$user_var});
              $actual_transitions = array_map(function ($transition) {
                return $transition->getId();
              }, $actual_transitions);
              sort($actual_transitions);
              sort($transitions);

              $moderated_message = $parent_moderation ? 'pre moderated' : 'post moderated';
              $message = "Transitions do not match for user $user_var, state $content_state and a $moderated_message $parent_bundle for a parent (eLibrary: $e_library_state).";
              $this->assertEquals($transitions, $actual_transitions, $message);
            }
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
  abstract protected function createAccessProvider();

  /**
   * Provides data for access check.
   *
   * The structure of the array is:
   * @code
   * $access_array = [
   *  'parent_bundle' => [
   *    'parent_state' => [
   *      'parent_moderation' => [
   *        'entity_state' => [
   *          'operation' => [
   *            'allowed user 1',
   *            'allowed user 2',
   *           ],
   *         ],
   *       ],
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
  abstract protected function readUpdateDeleteAccessProvider();

  /**
   * Provides data for transition checks.
   *
   * The structure of the array is:
   * @code
   * $workflow_array = [
   *   'parent_bundle' => [
   *     'parent_e_library' => [
   *       'parent_moderation' => [
   *         'entity_state' => [
   *           'user' => [
   *             'transition',
   *             'transition',
   *           ],
   *         ],
   *       ],
   *     ],
   *   ],
   * ];
   * @code
   * There can be multiple transitions that can lead to a specific state, so
   * the check is being done on allowed transitions.
   */
  abstract protected function workflowTransitionsProvider();

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
  abstract protected function isPublishedState($state);

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
      $field_identifier[$bundle] . 'elibrary_creation' => $e_library,
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

}
