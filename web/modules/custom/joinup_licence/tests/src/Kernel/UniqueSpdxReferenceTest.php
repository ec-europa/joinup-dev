<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_licence\Kernel;

use Drupal\Tests\joinup_core\Kernel\JoinupKernelTestBase;
use Drupal\joinup_core\Plugin\Validation\Constraint\UniqueFieldInBundleConstraint;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Tests the validation on the licence bundle entity.
 *
 * @group entity_validation
 */
class UniqueSpdxReferenceTest extends JoinupKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'joinup_licence',
    'field_group',
    'smart_trim',
    'spdx',
    'allowed_formats',
    'comment',
    'image',
    'joinup_core',
    'node',
    'oe_newsroom_newsletter',
    'og',
    'rdf_taxonomy',
    'rdf_entity',
    'rdf_schema_field_validation',
    'taxonomy',
    'tour',
  ];

  /**
   * A list of entities.
   *
   * @var \Drupal\Core\Entity\EntityInterface[]
   */
  protected $entities = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig('joinup_core');
    $this->installConfig('joinup_licence');
    $this->installConfig('spdx');
  }

  /**
   * Test that references to SPDX licences are unique.
   */
  public function testUniqueSpdxReference() {
    $licence_type_id = 'http://purl.org/adms/licencetype/Attribution';
    $this->entities['spdx'] = Rdf::create([
      'label' => 'Test SPDX',
      'rid' => 'spdx_licence',
    ]);
    $this->entities['spdx']->save();
    $spdx_id = $this->entities['spdx']->id();

    $this->entities['licence1'] = Rdf::create([
      'label' => 'Licence 1',
      'rid' => 'licence',
      'field_licence_description' => ['value' => 'Some description'],
      'field_licence_type' => ['target_id' => $licence_type_id],
      'field_licence_spdx_licence' => ['target_id' => $spdx_id],
    ]);
    $this->entities['licence1']->save();

    $licence = Rdf::create([
      'label' => 'Licence 2',
      'rid' => 'licence',
      'field_licence_description' => ['value' => 'Some description'],
      'field_licence_type' => ['target_id' => $licence_type_id],
      'field_licence_spdx_licence' => ['target_id' => $spdx_id],
    ]);

    /** @var \Drupal\Core\Entity\EntityConstraintViolationList $violations */
    $violations = $licence->validate();
    $this->assertCount(1, $violations);
    $violation = $violations[0];
    $this->assertEquals(UniqueFieldInBundleConstraint::class, get_class($violation->getConstraint()));
    $this->assertEquals('field_licence_spdx_licence', $violation->getPropertyPath());
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    parent::tearDown();

    if (!empty($this->entities)) {
      foreach ($this->entities as $entity) {
        $entity->delete();
      }
    }
  }

}
