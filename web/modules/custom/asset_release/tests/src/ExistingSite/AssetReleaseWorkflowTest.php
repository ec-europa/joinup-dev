<?php

declare(strict_types = 1);

namespace Drupal\Tests\asset_release\ExistingSite;

use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Tests\joinup_workflow\ExistingSite\JoinupWorkflowExistingSiteTestBase;
use Drupal\og\Entity\OgRole;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\RdfInterface;

/**
 * Tests crud operations and the workflow for the asset release rdf entity.
 *
 * @group asset_release
 */
class AssetReleaseWorkflowTest extends JoinupWorkflowExistingSiteTestBase {

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
   * A user to be used as a solution facilitator.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $userOgFacilitator;

  /**
   * A user to be used as a solution administrator.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $userOgAdministrator;

  /**
   * The solution parent entity.
   *
   * @var \Drupal\rdf_entity\RdfInterface
   */
  protected $solutionGroup;

  /**
   * The solution facilitator role.
   *
   * @var \Drupal\og\Entity\OgRole
   */
  protected $roleFacilitator;

  /**
   * The solution administrator role.
   *
   * @var \Drupal\og\Entity\OgRole
   */
  protected $roleAdministrator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->userAnonymous = new AnonymousUserSession();
    $this->userAuthenticated = $this->createUser();
    $this->userModerator = $this->createUser([], NULL, FALSE, ['roles' => ['moderator']]);
    $this->userOgFacilitator = $this->createUser();
    $this->userOgAdministrator = $this->createUser();

    $this->roleFacilitator = OgRole::getRole('rdf_entity', 'solution', 'facilitator');
    $this->roleAdministrator = OgRole::getRole('rdf_entity', 'solution', 'administrator');
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityType(): string {
    return 'rdf_entity';
  }

  /**
   * Tests the CRUD operations for the asset release entities.
   *
   * Since the browser test is a slow test, both create access and read/update/
   * delete access are tested below.
   */
  public function testCrudAccess(): void {
    // Test create access.
    foreach ($this->createAccessProvider() as $parent_state => $test_data_arrays) {
      $parent = $this->createDefaultParent($parent_state);
      // Initialize the release entity as is used in all sub-cases.
      $content = $this->createRdfEntity([
        'rid' => 'asset_release',
        'field_isr_is_version_of' => $parent->id(),
      ]);
      foreach ($test_data_arrays as $test_data) {
        $operation = 'create';
        $user_var = $test_data[0];
        $expected_result = $test_data[1];

        $access = $this->ogAccess->userAccessEntityOperation('create', $content, $this->$user_var)->isAllowed();
        $result = $expected_result ? $this->t('have') : $this->t('not have');
        $message = "User {$user_var} should {$result} {$operation} access for bundle 'asset_release' with the parent entity in {$parent_state} state.";
        $this->assertEquals($expected_result, $access, $message);
      }
    }

    // Test view, update, delete access.
    foreach ($this->readUpdateDeleteAccessProvider() as $parent_state => $content_data) {
      $parent = $this->createDefaultParent($parent_state);

      foreach ($content_data as $content_state => $test_data_arrays) {
        // Initialize the release entity as it is going to be used in all sub
        // cases.
        $content = $this->createRdfEntity([
          'rid' => 'asset_release',
          'label' => $this->randomMachineName(),
          'field_isr_state' => $content_state,
          'field_isr_is_version_of' => $parent->id(),
        ]);

        foreach ($test_data_arrays as $test_data_array) {
          $operation = $test_data_array[0];
          $user_var = $test_data_array[1];
          $expected_result = $test_data_array[2];

          $access = $this->entityAccess->access($content, $operation, $this->$user_var);
          $result = $expected_result ? $this->t('have') : $this->t('not have');
          $message = "User {$user_var} should {$result} {$operation} access for entity {$content->label()} ({$content_state}) with the parent entity in {$parent_state} state.";
          $this->assertEquals($expected_result, $access, $message);
        }
      }
    }
  }

  /**
   * Tests the asset release workflow.
   */
  public function testWorkflow() {
    foreach ($this->workflowTransitionsProvider() as $entity_state => $workflow_data) {
      $parent = $this->createDefaultParent('validated');

      foreach ($workflow_data as $user_var => $expected_target_states) {
        $content = Rdf::create([
          'rid' => 'asset_release',
          'label' => $this->randomMachineName(),
          'field_isr_state' => $entity_state,
          'field_isr_is_version_of' => $parent->id(),
        ]);
        $content->save();

        // Override the user to be checked for the allowed transitions.
        $actual_target_states = $this->workflowHelper->getAvailableTargetStates($content, $this->$user_var);
        sort($actual_target_states);
        sort($expected_target_states);

        $this->assertEquals($expected_target_states, $actual_target_states, 'Allowed transitions match with settings.');
      }
    }
  }

  /**
   * Generates a solution entity and initializes default memberships.
   *
   * @param string $state
   *   The state of the entity.
   *
   * @return \Drupal\rdf_entity\RdfInterface
   *   The created solution entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   If the entity fails to create.
   */
  protected function createDefaultParent(string $state): RdfInterface {
    // Make sure the current user is set to anonymous when creating solutions
    // through the API so we can assign the administrator manually. If a user is
    // logged in during creation of the solution they will automatically become
    // the administrator.
    $this->setCurrentUser($this->userAnonymous);

    $parent = $this->createRdfEntity([
      'rid' => 'solution',
      'field_is_state' => $state,
      'label' => $this->randomMachineName(),
      // Ensure a parent collection.
      'collection' => $this->createRdfEntity([
        'rid' => 'collection',
        'label' => $this->randomString(),
        'field_ar_state' => 'validated',
      ]),
    ]);
    $this->assertInstanceOf(RdfInterface::class, $parent);
    $this->createOgMembership($parent, $this->userOgFacilitator, [$this->roleFacilitator]);
    $this->createOgMembership($parent, $this->userOgAdministrator, [$this->roleAdministrator]);
    return $parent;
  }

  /**
   * Provides data for create access check.
   *
   * The access to create a release is checked against the parent entity as it
   * is dependant to og permissions.
   * The structure of the array is:
   * @code
   * $access_array = [
   *  'parent_state' => [
   *    ['user1', 'expected_result'],
   *    ['user2', 'expected_result'],
   * ];
   * @code
   * The user variable represents the variable defined in the test.
   * Only two parent states need to be tested as the expected result might
   * differ depending on whether the parent is published or not.
   *
   * @return array
   *   Testing data.
   */
  public function createAccessProvider(): array {
    return [
      // Unpublished parent.
      'draft' => [
        ['userAnonymous', FALSE],
        ['userAuthenticated', FALSE],
        ['userModerator', TRUE],
        ['userOgFacilitator', TRUE],
        ['userOgAdministrator', FALSE],
      ],
      // Published parent.
      'validated' => [
        ['userAnonymous', FALSE],
        ['userAuthenticated', FALSE],
        ['userModerator', TRUE],
        ['userOgFacilitator', TRUE],
        ['userOgAdministrator', FALSE],
      ],
    ];
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
   *
   * @return array
   *   Testing data.
   */
  public function readUpdateDeleteAccessProvider(): array {
    return [
      // Unpublished parent.
      'draft' => [
        'draft' => [
          ['view', 'userAnonymous', FALSE],
          ['view', 'userAuthenticated', FALSE],
          ['view', 'userModerator', TRUE],
          ['view', 'userOgFacilitator', TRUE],
          ['view', 'userOgAdministrator', FALSE],
          ['update', 'userAnonymous', FALSE],
          ['update', 'userAuthenticated', FALSE],
          ['update', 'userModerator', TRUE],
          ['update', 'userOgFacilitator', TRUE],
          ['update', 'userOgAdministrator', FALSE],
          ['delete', 'userAnonymous', FALSE],
          ['delete', 'userAuthenticated', FALSE],
          ['delete', 'userModerator', TRUE],
          ['delete', 'userOgFacilitator', FALSE],
          ['delete', 'userOgAdministrator', FALSE],
        ],
        'validated' => [
          ['view', 'userAnonymous', FALSE],
          ['view', 'userAuthenticated', FALSE],
          ['view', 'userModerator', TRUE],
          ['view', 'userOgFacilitator', TRUE],
          ['view', 'userOgAdministrator', FALSE],
          ['update', 'userAnonymous', FALSE],
          ['update', 'userAuthenticated', FALSE],
          ['update', 'userModerator', TRUE],
          ['update', 'userOgFacilitator', TRUE],
          ['update', 'userOgAdministrator', FALSE],
          ['delete', 'userAnonymous', FALSE],
          ['delete', 'userAuthenticated', FALSE],
          ['delete', 'userModerator', TRUE],
          ['delete', 'userOgFacilitator', FALSE],
          ['delete', 'userOgAdministrator', FALSE],
        ],
        'needs_update' => [
          ['view', 'userAnonymous', FALSE],
          ['view', 'userAuthenticated', FALSE],
          ['view', 'userModerator', TRUE],
          ['view', 'userOgFacilitator', TRUE],
          ['view', 'userOgAdministrator', FALSE],
          ['update', 'userAnonymous', FALSE],
          ['update', 'userAuthenticated', FALSE],
          ['update', 'userModerator', TRUE],
          ['update', 'userOgFacilitator', TRUE],
          ['update', 'userOgAdministrator', FALSE],
          ['delete', 'userAnonymous', FALSE],
          ['delete', 'userAuthenticated', FALSE],
          ['delete', 'userModerator', TRUE],
          ['delete', 'userOgFacilitator', FALSE],
          ['delete', 'userOgAdministrator', FALSE],
        ],
      ],
      // Published parent.
      'validated' => [
        'draft' => [
          ['view', 'userAnonymous', FALSE],
          ['view', 'userAuthenticated', FALSE],
          ['view', 'userModerator', TRUE],
          ['view', 'userOgFacilitator', TRUE],
          ['view', 'userOgAdministrator', FALSE],
          ['update', 'userAnonymous', FALSE],
          ['update', 'userAuthenticated', FALSE],
          ['update', 'userModerator', TRUE],
          ['update', 'userOgFacilitator', TRUE],
          ['update', 'userOgAdministrator', FALSE],
          ['delete', 'userAnonymous', FALSE],
          ['delete', 'userAuthenticated', FALSE],
          ['delete', 'userModerator', TRUE],
          ['delete', 'userOgFacilitator', FALSE],
          ['delete', 'userOgAdministrator', FALSE],
        ],
        'validated' => [
          ['view', 'userAnonymous', TRUE],
          ['view', 'userAuthenticated', TRUE],
          ['view', 'userModerator', TRUE],
          ['view', 'userOgFacilitator', TRUE],
          ['view', 'userOgAdministrator', TRUE],
          ['update', 'userAnonymous', FALSE],
          ['update', 'userAuthenticated', FALSE],
          ['update', 'userModerator', TRUE],
          ['update', 'userOgFacilitator', TRUE],
          ['update', 'userOgAdministrator', FALSE],
          ['delete', 'userAnonymous', FALSE],
          ['delete', 'userAuthenticated', FALSE],
          ['delete', 'userModerator', TRUE],
          ['delete', 'userOgFacilitator', FALSE],
          ['delete', 'userOgAdministrator', FALSE],
        ],
        'needs_update' => [
          ['view', 'userAnonymous', FALSE],
          ['view', 'userAuthenticated', FALSE],
          ['view', 'userModerator', TRUE],
          ['view', 'userOgFacilitator', TRUE],
          ['view', 'userOgAdministrator', FALSE],
          ['update', 'userAnonymous', FALSE],
          ['update', 'userAuthenticated', FALSE],
          ['update', 'userModerator', TRUE],
          ['update', 'userOgFacilitator', TRUE],
          ['update', 'userOgAdministrator', FALSE],
          ['delete', 'userAnonymous', FALSE],
          ['delete', 'userAuthenticated', FALSE],
          ['delete', 'userModerator', TRUE],
          ['delete', 'userOgFacilitator', FALSE],
          ['delete', 'userOgAdministrator', FALSE],
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
   *
   * @return array
   *   Testing data.
   */
  public function workflowTransitionsProvider(): array {
    return [
      'draft' => [
        'userAuthenticated' => [],
        'userModerator' => [
          'draft',
          'validated',
        ],
        'userOgFacilitator' => [
          'draft',
          'validated',
        ],
        'userOgAdministrator' => [],
      ],
      'validated' => [
        'userAuthenticated' => [],
        'userModerator' => [
          'draft',
          'validated',
          'needs_update',
        ],
        'userOgFacilitator' => [
          'draft',
          'validated',
        ],
        'userOgAdministrator' => [],
      ],
      'needs_update' => [
        'userAuthenticated' => [],
        'userModerator' => [
          'needs_update',
          'validated',
        ],
        'userOgFacilitator' => [
          'needs_update',
        ],
        'userOgAdministrator' => [],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityBundle(): string {
    return 'asset_release';
  }

}
