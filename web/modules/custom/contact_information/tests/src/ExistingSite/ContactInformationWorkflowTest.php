<?php

declare(strict_types = 1);

namespace Drupal\Tests\contact_information\ExistingSite;

use Drupal\Core\Session\AnonymousUserSession;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\Tests\joinup_core\ExistingSite\JoinupWorkflowExistingSiteTestBase;

/**
 * Tests the workflow for the contact information entity.
 *
 * @group contact_information
 */
class ContactInformationWorkflowTest extends JoinupWorkflowExistingSiteTestBase {

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
  public function setUp(): void {
    parent::setUp();

    $this->userAnonymous = new AnonymousUserSession();
    $this->userAuthenticated = $this->createUser();
    $this->userModerator = $this->createUser([], NULL, FALSE, ['roles' => ['moderator']]);
    $this->userOwner = $this->createUser();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityType(): string {
    return 'rdf_entity';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityBundle(): string {
    return 'contact_information';
  }

  /**
   * Tests the workflow for contact information entities.
   */
  public function testWorkflow(): void {
    foreach ($this->workflowTransitionsProvider() as $entity_state => $workflow_data) {
      foreach ($workflow_data as $user_var => $expected_states) {
        // The created entity is not saved because this will trigger the
        // '__new__' state to be converted to 'validated'.
        // @see: contact_information_rdf_entity_presave().
        $content = Rdf::create([
          'rid' => 'contact_information',
          'label' => $this->randomMachineName(),
          'field_ci_state' => $entity_state,
        ]);

        // Override the user to be checked for the allowed transitions.
        $actual_states = $this->workflowHelper->getAvailableStates($content, $this->$user_var);

        sort($actual_states);
        sort($expected_states);

        $this->assertEquals($expected_states, $actual_states, "Transitions do not match for user $user_var, state $entity_state.");
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
   *
   * @return array
   *   Test cases.
   */
  public function workflowTransitionsProvider(): array {
    return [
      '__new__' => [
        'userAuthenticated' => [
          'validated',
        ],
        'userModerator' => [
          'validated',
        ],
      ],
      'validated' => [
        'userAuthenticated' => [
          'validated',
          'deletion_request',
        ],
        'userModerator' => [
          'validated',
          'needs_update',
          'deletion_request',
        ],
      ],
      'needs_update' => [
        'userAuthenticated' => [
          'needs_update',
        ],
        'userModerator' => [
          'needs_update',
          'validated',
        ],
      ],
    ];
  }

}
