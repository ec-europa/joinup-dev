<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_group\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\entity_test\Entity\EntityTest;

/**
 * Tests the UniqueFieldValueInGroupAndBundle constraint.
 *
 * @group joinup_group
 */
class UniqueFieldValueInGroupAndBundleTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * Testing group entities.
   *
   * @var \Drupal\entity_test\Entity\EntityTest[]
   */
  protected $group;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'field',
    'joinup_group',
    'og',
    'system',
    'unique_field_value_in_group_and_bundle_test',
    'workflow_state_permission',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['og', 'unique_field_value_in_group_and_bundle_test']);
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);

    // Create two group bundles.
    entity_test_create_bundle('group');
    entity_test_create_bundle('other_group');
    $this->container->get('config.factory')->getEditable('og.settings')
      ->set('groups.entity_test', ['group', 'other_group'])
      ->save();

    // Create two group content bundles.
    entity_test_create_bundle('group_content', 'Group 1');
    entity_test_create_bundle('other_group_content', 'Group 2');

    // Create groups.
    $this->group['group1'] = EntityTest::create(['type' => 'group']);
    $this->group['group2'] = EntityTest::create(['type' => 'group']);
    array_walk($this->group, function (EntityTest $entity): void {
      $entity->save();
    });
  }

  /**
   * Tests that entity types/bundles that are not group content are ignored.
   */
  public function testIgnoreNonGroupContentEntityType(): void {
    $this->createEntity('entity_test', 'Non-group entity')->save();
    $violations = $this->createEntity('entity_test', 'Non-group entity')->validate();
    $this->assertCount(0, $violations);
  }

  /**
   * Tests a constraint misconfigured with non-existing field.
   */
  public function testInvalidConstraintOptionNonExistingGroupField(): void {
    $this->setGroupAudienceFieldOption('non_existing_field');
    $this->expectExceptionObject(new \InvalidArgumentException("Bundle 'group_content' of 'entity_test' entity type has no 'non_existing_field' group audience field."));
    $this->createEntity('group_content', 'Group content entity')->validate();
  }

  /**
   * Tests a constraint misconfigured with a non-'group audience' field.
   */
  public function testInvalidConstraintOptionNonGroupField(): void {
    $this->setGroupAudienceFieldOption('user_id');
    $this->expectExceptionObject(new \InvalidArgumentException("Bundle 'group_content' of 'entity_test' entity type has no 'user_id' group audience field."));
    $this->createEntity('group_content', 'Group content entity')->validate();
  }

  /**
   * Tests that name' field duplicates can be created when group is not set.
   */
  public function testGroupAudienceNotSet(): void {
    $this->createEntity('group_content', 'Group content entity')->save();
    $violations = $this->createEntity('group_content', 'Group content entity')->validate();
    $this->assertCount(0, $violations);
  }

  /**
   * Tests name duplication in several scenarios.
   */
  public function testDuplicateField(): void {
    // Within the same bundle and group, the 'name' field cannot be duplicated.
    $this->createEntity('group_content', 'Group content entity', 'group1')->save();
    $violations = $this->createEntity('group_content', 'Group content entity', 'group1')->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals('The Group 1 name value (Group content entity) is already taken by Group content entity.', strip_tags((string) $violations->get(0)->getMessage()));

    // Within the same bundle but different groups, the name can be duplicated.
    $this->createEntity('group_content', 'Group content entity', 'group1')->save();
    $violations = $this->createEntity('group_content', 'Group content entity', 'group2')->validate();
    $this->assertCount(0, $violations);

    // Within the same group but different bundles, the name can be duplicated.
    $this->createEntity('group_content', 'Group content entity', 'group1')->save();
    $violations = $this->createEntity('other_group_content', 'Group content entity', 'group1')->validate();
    $this->assertCount(0, $violations);

    // Within different group and bundle, the name can be duplicated.
    $this->createEntity('group_content', 'Group content entity', 'group1')->save();
    $violations = $this->createEntity('other_group_content', 'Group content entity', 'group2')->validate();
    $this->assertCount(0, $violations);
  }

  /**
   * Create, without saving, a testing entity.
   *
   * @param string $type
   *   The entity bundle.
   * @param string $name
   *   The name (entity label).
   * @param string|null $group
   *   (optional) The group reference, if any.
   *
   * @return \Drupal\entity_test\Entity\EntityTest
   *   The created but not saved entity.
   */
  protected function createEntity(string $type, string $name, ?string $group = NULL): EntityTest {
    $values = [
      'name' => $name,
      'type' => $type,
      'user_id' => $this->createUser(),
    ];
    if ($group) {
      $values += ['group' => $this->group[$group]];
    }
    return EntityTest::create($values);
  }

  /**
   * Sets the group audience field to be configured with the constraint.
   *
   * @param string $field
   *   The name of the OG group audience field.
   *
   * @see unique_field_value_in_group_and_bundle_test_entity_base_field_info_alter()
   */
  protected function setGroupAudienceFieldOption(string $field): void {
    $this->container->get('state')->set('unique_field_value_in_group_and_bundle_test', $field);
    $this->container->get('entity_field.manager')->clearCachedFieldDefinitions();
  }

}
