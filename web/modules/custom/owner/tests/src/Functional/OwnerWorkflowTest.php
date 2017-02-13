<?php

namespace Drupal\Tests\owner\Functional;

use Drupal\Core\Session\AnonymousUserSession;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\Tests\joinup_core\JoinupWorkflowTestBase;

/**
 * Tests crud operations and the workflow for the owner rdf entity.
 *
 * @group owner
 */
class OwnerWorkflowTest extends JoinupWorkflowTestBase {

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
   * A user that will be set as owner of the entity.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $userOwner;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->userAnonymous = new AnonymousUserSession();
    $this->userAuthenticated = $this->createUserWithRoles();
    $this->userModerator = $this->createUserWithRoles(['moderator']);
    $this->userOwner = $this->createUserWithRoles();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityType() {
    return 'rdf_entity';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityBundle() {
    return 'owner';
  }

  /**
   * Tests the CRUD operations for the asset release entities.
   *
   * Since the browser test is a slow test, both create access and read/update/
   * delete access are tested below.
   */
  public function testCrudAccess() {
    // Test create access.
    foreach ($this->createAccessProvider() as $user_var => $expected_result) {
      $access = $this->entityAccess->createAccess('owner', $this->{$user_var});
      $result = $expected_result ? t('have') : t('not have');
      $message = "User {$user_var} should {$result} create access for bundle 'owner'.";
      $this->assertEquals($expected_result, $access, $message);
    }

    // A list of possible users.
    $available_users = [
      'userAnonymous',
      'userAuthenticated',
      'userModerator',
      'userOwner',
    ];

    // Test view, update, delete access.
    foreach ($this->readUpdateDeleteAccessProvider() as $entity_state => $test_data) {
      $content = Rdf::create([
        'rid' => 'owner',
        'label' => $this->randomMachineName(),
        'field_owner_state' => $entity_state,
        'uid' => $this->userOwner->id(),
      ]);
      $content->save();

      foreach ($test_data as $operation => $allowed_users) {
        foreach ($available_users as $user_var) {
          $this->userProvider->setUser($this->{$user_var});

          // If the current user is found in the allowed list, the expected
          // access result is true, otherwise false.
          $expected_result = in_array($user_var, $allowed_users);

          $access = $this->entityAccess->access($content, $operation, $this->{$user_var});
          $result = $expected_result ? t('have') : t('not have');
          $message = "User {$user_var} should {$result} {$operation} access for entity {$content->label()} ({$entity_state}).";
          $this->assertEquals($expected_result, $access, $message);
        }
      }

      // To save code (or for lazyness?) we are reusing the same owner entity,
      // referencing it in a collection. The entity access handler has static
      // caching that needs to be cleared to properly run the access checks on
      // the content.
      $this->entityAccess->resetCache();

      // Owner entities that are referenced in other ones cannot be deleted.
      $parent = Rdf::create([
        'rid' => 'collection',
        'label' => $this->randomMachineName(),
        'uid' => $this->userOwner->id(),
        'field_ar_state' => 'draft',
        'field_ar_owner' => $content->id(),
      ]);
      $parent->save();
      foreach ($available_users as $user_var) {
        $this->userProvider->setUser($this->{$user_var});
        $this->assertFalse($this->entityAccess->access($content, 'delete', $this->{$user_var}), "User {$user_var} should not have delete access for entity {$content->label()} ({$entity_state}).");
      }
    }
  }

  /**
   * Tests the owner workflow.
   */
  public function testWorkflow() {
    foreach ($this->workflowTransitionsProvider() as $entity_state => $workflow_data) {
      foreach ($workflow_data as $user_var => $transitions) {
        $content = Rdf::create([
          'rid' => 'owner',
          'label' => $this->randomMachineName(),
        ]);
        // Override the default state of 'validated' that is set during entity
        // creation.
        // @see owner_rdf_entity_create()
        $content->set('field_owner_state', $entity_state);
        $content->save();

        // Override the user to be checked for the allowed transitions.
        $this->userProvider->setUser($this->{$user_var});
        $actual_transitions = $content->get('field_owner_state')->first()->getTransitions();
        $actual_transitions = array_map(function ($transition) {
          return $transition->getId();
        }, $actual_transitions);
        sort($actual_transitions);
        sort($transitions);

        $this->assertEquals($transitions, $actual_transitions, "Transitions do not match for user $user_var, state $entity_state.");
      }
    }
  }

  /**
   * Provides data for create access check.
   */
  public function createAccessProvider() {
    return [
      'userAnonymous' => FALSE,
      'userAuthenticated' => TRUE,
      'userModerator' => TRUE,
    ];
  }

  /**
   * Provides data for access check.
   *
   * The structure of the array is:
   * @code
   * $access_array = [
   *   'entity_state' => [
   *     'operation' => [
   *        'allowed_role1',
   *        'allowed_role2',
   *     ],
   *   ],
   * ];
   * @code
   */
  public function readUpdateDeleteAccessProvider() {
    return [
      'validated' => [
        'view' => [
          'userAnonymous',
          'userAuthenticated',
          'userModerator',
          'userOwner',
        ],
        'edit' => [
          'userModerator',
          'userOwner',
        ],
        'delete' => [
          'userModerator',
        ],
      ],
      'in_assessment' => [
        'view' => [
          'userAnonymous',
          'userAuthenticated',
          'userModerator',
          'userOwner',
        ],
        'edit' => [
          'userModerator',
          'userOwner',
        ],
        'delete' => [
          'userModerator',
        ],
      ],
      'deletion_request' => [
        'view' => [
          'userAnonymous',
          'userAuthenticated',
          'userModerator',
          'userOwner',
        ],
        'edit' => [
          'userModerator',
        ],
        'delete' => [
          'userModerator',
        ],
      ],
    ];
  }

  /**
   * Provides data for transition checks.
   *
   * The structure of the array is:
   * @code
   * $workflow_array = [
   *   'entity_state' => [
   *     'user' => [
   *       'transition',
   *       'transition',
   *     ],
   *   ],
   * ];
   * @code
   * There can be multiple transitions that can lead to a specific state, so
   * the check is being done on allowed transitions.
   */
  public function workflowTransitionsProvider() {
    return [
      '__new__' => [
        'userAuthenticated' => [
          'validate',
        ],
        'userModerator' => [
          'validate',
        ],
      ],
      'validated' => [
        'userAuthenticated' => [
          'update_published',
          'request_deletion',
        ],
        'userModerator' => [
          'update_published',
          'request_changes',
          'request_deletion',
        ],
      ],
      'in_assessment' => [
        'userAuthenticated' => [
          'update_changes',
        ],
        'userModerator' => [
          'update_changes',
          'approve_changes',
        ],
      ],
    ];
  }

}
