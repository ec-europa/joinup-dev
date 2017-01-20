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
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->userAnonymous = new AnonymousUserSession();
    $this->userAuthenticated = $this->createUserWithRoles();
    $this->userModerator = $this->createUserWithRoles(['moderator']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityType() {
    return 'rdf_entity';
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
          'field_owner_state' => $entity_state,
        ]);
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
