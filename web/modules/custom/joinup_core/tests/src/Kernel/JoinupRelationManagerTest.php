<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_core\Kernel;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Entity\OgRole;
use Drupal\og\OgMembershipInterface;
use Drupal\og\OgRoleInterface;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\RdfInterface;
use Drupal\Tests\rdf_entity\Traits\RdfDatabaseConnectionTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Assert;

/**
 * Tests for the Joinup relation manager service.
 *
 * @group joinup_core
 * @coversDefaultClass \Drupal\joinup_core\JoinupRelationManager
 */
class JoinupRelationManagerTest extends KernelTestBase {

  use RdfDatabaseConnectionTrait;
  use RdfEntityTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'allowed_formats',
    'asset_distribution',
    'asset_release',
    'cached_computed_field',
    'collection',
    'comment',
    'datetime',
    'ds',
    'facets',
    'field',
    'field_group',
    'file',
    'file_url',
    'filter',
    'image',
    'inline_entity_form',
    'joinup_core',
    'link',
    'node',
    'og',
    'og_menu',
    'options',
    'menu_link_content',
    'piwik_reporting_api',
    'rdf_draft',
    'rdf_entity',
    'rdf_taxonomy',
    'search_api',
    'search_api_field',
    'smart_trim',
    'solution',
    'state_machine',
    'system',
    'taxonomy',
    'text',
    'user',
  ];

  /**
   * The Joinup relation manager service. This is the system under test.
   *
   * @var \Drupal\joinup_core\JoinupRelationManagerInterface
   */
  protected $joinupRelationManager;

  /**
   * The OG group type manager.
   *
   * @var \Drupal\og\GroupTypeManagerInterface
   */
  protected $groupTypeManager;

  /**
   * A collection of test RDF entities.
   *
   * @var \Drupal\rdf_entity\RdfInterface[][]
   */
  protected $testRdfEntities = [];

  /**
   * A collection of test users.
   *
   * @var \Drupal\user\Entity\User[]
   */
  protected $testUsers = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->setUpSparql();

    $this->installEntitySchema('rdf_entity');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('file');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('ogmenu');
    $this->installEntitySchema('ogmenu_instance');
    $this->installEntitySchema('menu_link_content');

    // Note that the order in which these are being installed is significant.
    // Dependencies need to be installed first.
    $this->installConfig([
      'rdf_entity',
      'rdf_draft',
      'joinup_core',
      'asset_release',
      'collection',
      'solution',
    ]);
    $this->installSchema('file', ['file_usage']);
    $this->installSchema('system', ['sequences']);

    $this->joinupRelationManager = $this->container->get('joinup_core.relations_manager');

    // Define collections and solutions as group types. This is configuration
    // which is defined in the Joinup profile, but we do not want to install the
    // full profile for performance reasons.
    $og_settings = $this->config('og.settings');

    $groups = $og_settings->get('groups');
    $groups['rdf_entity'] = ['collection', 'solution'];

    $og_settings->set('groups', $groups);
    $og_settings->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    // Clean up the test entities that were created in the setup.
    foreach ($this->testRdfEntities as $bundle_id => $entities) {
      foreach ($entities as $entity) {
        $entity->delete();
      }
    }

    parent::tearDown();
  }

  /**
   * @covers ::getGroupMembershipsByRoles
   * @dataProvider getGroupMembershipsByRolesDataProvider
   */
  public function testGetGroupMembershipsByRoles(string $bundle_id, int $group_key, array $role_names, array $states, array $expected): void {
    $this->createGroupMembershipTree();

    $entity = $this->testRdfEntities[$bundle_id][$group_key];

    $memberships = $this->joinupRelationManager->getGroupMembershipsByRoles($entity, $role_names, $states);
  }

  /**
   * Data provider for testing retrieval of group memberships by roles.
   *
   * @see testGetGroupMembershipsByRoles()
   */
  public function getGroupMembershipsByRolesDataProvider() {
    return [
      ['collection', 0, ['member'], [OgMembershipInterface::STATE_ACTIVE], []],
    ];
  }

  /**
   * @covers ::getCollectionIds
   */
  public function testGetCollectionIds(): void {
    $this->createTestGroups('collection', 2);
    $this->assertRdfEntityIds('collection', $this->joinupRelationManager->getCollectionIds());
  }

  /**
   * @covers ::getSolutionIds
   */
  public function testGetSolutionIds(): void {
    $this->createTestGroups('solution', 2);
    $this->assertRdfEntityIds('solution', $this->joinupRelationManager->getSolutionIds());
  }

  /**
   * Checks that the given RDF entity IDs match those defined in the setup.
   *
   * @param string $bundle_id
   *   The RDF entity bundle of the entities to check.
   * @param array $ids
   *   The entity IDs retrieved from the database.
   */
  protected function assertRdfEntityIds(string $bundle_id, array $ids): void {
    $expected_ids = array_map(function (RdfInterface $entity): string {
      return $entity->id();
    }, $this->testRdfEntities[$bundle_id]);

    sort($ids);
    sort($expected_ids);

    try {
      Assert::assertEquals($expected_ids, $ids);
    }
    catch (\Exception $e) {
      foreach ($ids as $id) {
        $entity = Rdf::load($id);
        var_dump($id, $entity->label()); ob_flush();
      }
      throw new \Exception('meh', 0, $e);
    }
  }

  /**
   * Creates a number of test groups.
   *
   * @param string $bundle_id
   *   The type of group to create, either 'collection' or 'solution'.
   * @param int $count
   *   The number of groups to create.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when an entity cannot be saved.
   */
  protected function createTestGroups(string $bundle_id, int $count): void {
    // Create two test collections and solutions.
    for ($i = 0; $i < $count; $i++) {
      $state_field_name = $bundle_id === 'collection' ? 'field_ar_state' : 'field_is_state';
      $this->testRdfEntities[$bundle_id][$i] = $this->createRdfEntity($bundle_id, [
        $state_field_name => 'draft',
      ]);
    }
  }

  /**
   * Creates a tree of group memberships to use in tests.
   */
  protected function createGroupMembershipTree(): void {
    // Create 2 collections and 2 solutions.
    foreach (['collection', 'solution'] as $type) {
      $this->createTestGroups($type, 2);
    }

    // Create 5 test users.
    for ($i = 0; $i < 5; $i++) {
      $this->testUsers[$i] = $this->createUser();
    }

    // Define a matrix of memberships, keyed by user.
    $membership_matrix = [
      0 => [
        0 => [
          'group_type' => 'collection',
          'group_key' => 0,
          'roles' => ['administrator'],
          'state' => OgMembershipInterface::STATE_ACTIVE,
        ],
        1 => [
          'group_type' => 'collection',
          'group_key' => 1,
          'roles' => ['member'],
          'state' => OgMembershipInterface::STATE_ACTIVE,
        ],
        2 => [
          'group_type' => 'solution',
          'group_key' => 0,
          'roles' => ['administrator', 'facilitator'],
          'state' => OgMembershipInterface::STATE_ACTIVE,
        ],
        3 => [
          'group_type' => 'solution',
          'group_key' => 1,
          'roles' => ['member'],
          'state' => OgMembershipInterface::STATE_PENDING,
        ],
      ],
      1 => [
        4 => [
          'group_type' => 'collection',
          'group_key' => 0,
          'roles' => ['facilitator'],
          'state' => OgMembershipInterface::STATE_ACTIVE,
        ],
        [
          'group_type' => 'collection',
          'group_key' => 1,
          'roles' => ['facilitator'],
          'state' => OgMembershipInterface::STATE_ACTIVE,
        ],
        [
          'group_type' => 'solution',
          'group_key' => 1,
          'roles' => ['facilitator'],
          'state' => OgMembershipInterface::STATE_ACTIVE,
        ],
      ],
      2 => [
        [
          'group_type' => 'collection',
          'group_key' => 0,
          'roles' => ['facilitator', 'member'],
          'state' => OgMembershipInterface::STATE_ACTIVE,
        ],
        [
          'group_type' => 'collection',
          'group_key' => 1,
          'roles' => ['administrator'],
          'state' => OgMembershipInterface::STATE_PENDING,
        ],
        [
          'group_type' => 'solution',
          'group_key' => 1,
          'roles' => ['member'],
          'state' => OgMembershipInterface::STATE_PENDING,
        ],
      ],
      3 => [
        [
          'group_type' => 'collection',
          'group_key' => 0,
          'roles' => ['member'],
          'state' => OgMembershipInterface::STATE_BLOCKED,
        ],
        [
          'group_type' => 'collection',
          'group_key' => 1,
          'roles' => ['member'],
          'state' => OgMembershipInterface::STATE_ACTIVE,
        ],
        [
          'group_type' => 'solution',
          'group_key' => 0,
          'roles' => ['member'],
          'state' => OgMembershipInterface::STATE_ACTIVE,
        ],
      ],
      4 => [
        [
          'group_type' => 'solution',
          'group_key' => 0,
          'roles' => ['member'],
          'state' => OgMembershipInterface::STATE_ACTIVE,
        ],
        [
          'group_type' => 'solution',
          'group_key' => 1,
          'roles' => ['administrator', 'facilitator'],
          'state' => OgMembershipInterface::STATE_ACTIVE,
        ],
      ],
    ];

    foreach ($membership_matrix as $user_key => $memberships) {
      $user = $this->testUsers[$user_key];
      foreach ($memberships as $membership_data) {
        $group = $this->testRdfEntities[$membership_data['group_type']][$membership_data['group_key']];
        $this->createOgMembership($group, $user, $membership_data['roles'], $membership_data['state']);
      }
    }
  }

  /**
   * Creates a test membership.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group for which to create the membership.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user for which to create the membership.
   * @param array $role_names
   *   Optional array of role names to assign to the membership. Defaults to the
   *   'member' role.
   * @param string $state
   *   Optional membership state. Can be one of the following constants:
   *   - OgMembershipInterface::STATE_ACTIVE
   *   - OgMembershipInterface::STATE_PENDING
   *   - OgMembershipInterface::STATE_BLOCKED
   *   Defaults to OgMembershipInterface::STATE_ACTIVE.
   * @param string $membership_type
   *   The membership type. Defaults to 'default'.
   *
   * @return \Drupal\og\OgMembershipInterface
   *   The membership.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when the membership cannot be created.
   *
   * @todo Replace this with OgMembershipCreationTrait once the upstream PR is
   *   accepted.
   *
   * @see https://github.com/Gizra/og/pull/394
   */
  protected function createOgMembership(EntityInterface $group, AccountInterface $user, array $role_names = NULL, $state = NULL, $membership_type = NULL) {
    // Provide default values.
    $role_names = $role_names ?: [OgRoleInterface::AUTHENTICATED];
    $state = $state ?: OgMembershipInterface::STATE_ACTIVE;
    $membership_type = $membership_type ?: OgMembershipInterface::TYPE_DEFAULT;

    $group_entity_type = $group->getEntityTypeId();
    $group_bundle = $group->bundle();

    $roles = array_map(function ($role_name) use ($group_entity_type, $group_bundle) {
      return OgRole::getRole($group_entity_type, $group_bundle, $role_name);
    }, $role_names);

    /** @var \Drupal\og\OgMembershipInterface $membership */
    $membership = OgMembership::create(['type' => $membership_type]);
    $membership
      ->setRoles($roles)
      ->setState($state)
      ->setOwner($user)
      ->setGroup($group)
      ->save();

    return $membership;
  }

}
