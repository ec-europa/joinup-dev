<?php

namespace Drupal\Tests\asset_release\Kernel;

use Drupal\og\Entity\OgRole;
use Drupal\og\Og;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\Tests\joinup_core\JoinupWorkflowTestBase;

/**
 * Tests the support of saving various encoded stings in the triple store.
 *
 * @group asset_release
 */
class AssetReleaseWorkflowTest extends JoinupWorkflowTestBase {

  protected $userAuthenticated;
  protected $userModerator;
  protected $userFacilitator;
  protected $userAdministrator;

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
  public function setUp() {
    parent::setUp();

    $this->setUpUsers();

    $this->roleFacilitator = OgRole::getRole('rdf_entity', 'solution', 'facilitator');
    $this->roleAdministrator = OgRole::getRole('rdf_entity', 'solution', 'administrator');
  }

  /**
   * Initialize users and memberships.
   */
  public function setUpUsers() {
    $this->userAuthenticated = $this->createUserWithRoles();
    $this->userModerator = $this->createUserWithRoles(['moderator']);
    $this->userFacilitator = $this->createUserWithRoles();
    $this->userAdministrator = $this->createUserWithRoles();
  }

  /**
   * Creates a user with roles.
   *
   * @param array $roles
   *    An array of roles to initialize the user with.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *    The created user object.
   */
  public function createUserWithRoles($roles = []) {
    $user = $this->createUser();
    foreach ($roles as $role) {
      $user->addRole($role);
    }
    $user->save();

    return $user;
  }

  /**
   * Tests view access.
   */
  public function testCrudAccess() {
    // @todo: Change the method name.
    foreach ($this->crudAccessProvider() as $parent_state => $content_data) {
      $this->solutionGroup = Rdf::create([
        'rid' => 'solution',
        'field_is_state' => $parent_state,
        'label' => $this->randomMachineName(),
      ]);
      $this->solutionGroup->save();

      Og::createMembership($this->solutionGroup, $this->userFacilitator)
        ->setRoles([$this->roleFacilitator])
        ->save();
      Og::createMembership($this->solutionGroup, $this->userAdministrator)
        ->setRoles([$this->roleAdministrator])
        ->save();

      foreach ($content_data as $content_state => $test_data_arrays) {
        $content = Rdf::create([
          'rid' => 'asset_release',
          'label' => $this->randomMachineName(),
          'field_isr_state' => $content_state,
          'field_isr_is_version_of' => $this->solutionGroup->id(),
        ]);
        $content->save();

        foreach ($test_data_arrays as $test_data_array) {
          $operation = $test_data_array[0];
          $user_var = $test_data_array[1];
          $expected_result = $test_data_array[2];

          $access = $this->ogAccess->userAccessEntity($operation, $content, $this->{$user_var});
          $result = $expected_result ? t('have') : t('not have');
          $message = "User {$user_var} should {$result} {$operation} access for entity {$content->label()} ({$content_state}).";
          $this->assertEquals($expected_result, $access->isAllowed(), $message);
        }
      }
    }
  }

  /**
   * Provides data for access check.
   */
  public function crudAccessProvider() {
    return [
      // Unpublished parent.
      'draft' => [
        'draft' => [
          ['view', 'userAuthenticated', FALSE],
          ['view', 'userModerator', TRUE],
          ['view', 'userFacilitator', TRUE],
          ['view', 'userAdministrator', FALSE],
          ['update', 'userAuthenticated', FALSE],
          ['update', 'userModerator', TRUE],
          ['update', 'userFacilitator', TRUE],
          ['update', 'userAdministrator', FALSE],
          ['delete', 'userAuthenticated', FALSE],
          ['delete', 'userModerator', TRUE],
          ['delete', 'userFacilitator', FALSE],
          ['delete', 'userAdministrator', FALSE],
        ],
        'validated' => [
          ['view', 'userAuthenticated', FALSE],
          ['view', 'userModerator', TRUE],
          ['view', 'userFacilitator', FALSE],
          ['view', 'userAdministrator', FALSE],
          ['update', 'userAuthenticated', FALSE],
          ['update', 'userModerator', TRUE],
          ['update', 'userFacilitator', TRUE],
          ['update', 'userAdministrator', FALSE],
          ['delete', 'userAuthenticated', FALSE],
          ['delete', 'userModerator', TRUE],
          ['delete', 'userFacilitator', FALSE],
          ['delete', 'userAdministrator', FALSE],
        ],
        'in_assessment' => [
          ['view', 'userAuthenticated', FALSE],
          ['view', 'userModerator', TRUE],
          ['view', 'userFacilitator', TRUE],
          ['view', 'userAdministrator', FALSE],
          ['update', 'userAuthenticated', FALSE],
          ['update', 'userModerator', TRUE],
          ['update', 'userFacilitator', TRUE],
          ['update', 'userAdministrator', FALSE],
          ['delete', 'userAuthenticated', FALSE],
          ['delete', 'userModerator', TRUE],
          ['delete', 'userFacilitator', FALSE],
          ['delete', 'userAdministrator', FALSE],
        ],
      ],
      // Published parent.
      'validated' => [
        'draft' => [
          ['view', 'userAuthenticated', FALSE],
          ['view', 'userModerator', TRUE],
          ['view', 'userFacilitator', FALSE],
          ['view', 'userAdministrator', FALSE],
          ['update', 'userAuthenticated', FALSE],
          ['update', 'userModerator', TRUE],
          ['update', 'userFacilitator', TRUE],
          ['update', 'userAdministrator', FALSE],
          ['delete', 'userAuthenticated', FALSE],
          ['delete', 'userModerator', TRUE],
          ['delete', 'userFacilitator', FALSE],
          ['delete', 'userAdministrator', FALSE],
        ],
        'validated' => [
          ['view', 'userAuthenticated', TRUE],
          ['view', 'userModerator', TRUE],
          ['view', 'userFacilitator', FALSE],
          ['view', 'userAdministrator', FALSE],
          ['update', 'userAuthenticated', FALSE],
          ['update', 'userModerator', TRUE],
          ['update', 'userFacilitator', TRUE],
          ['update', 'userAdministrator', FALSE],
          ['delete', 'userAuthenticated', FALSE],
          ['delete', 'userModerator', TRUE],
          ['delete', 'userFacilitator', FALSE],
          ['delete', 'userAdministrator', FALSE],
        ],
        'in_assessment' => [
          ['view', 'userAuthenticated', FALSE],
          ['view', 'userModerator', TRUE],
          ['view', 'userFacilitator', FALSE],
          ['view', 'userAdministrator', FALSE],
          ['update', 'userAuthenticated', FALSE],
          ['update', 'userModerator', TRUE],
          ['update', 'userFacilitator', TRUE],
          ['update', 'userAdministrator', FALSE],
          ['delete', 'userAuthenticated', FALSE],
          ['delete', 'userModerator', TRUE],
          ['delete', 'userFacilitator', FALSE],
          ['delete', 'userAdministrator', FALSE],
        ],
      ],
    ];
  }

}
