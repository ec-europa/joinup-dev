<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_core\ExistingSite;

use Drupal\Core\Validation\Plugin\Validation\Constraint\RegexConstraint;
use Drupal\Tests\joinup_test\ExistingSite\JoinupExistingSiteTestBase;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Tests the short ID allowed values.
 */
class ShortIdAllowedValuesTest extends JoinupExistingSiteTestBase {

  /**
   * Tests that empty short ID is allowed.
   */
  public function testEmptyShortId(): void {
    foreach (['collection', 'solution'] as $bundle) {
      $entity = Rdf::create(['rid' => $bundle]);;
      $violations = $entity->validate()->findByCodes([RegexConstraint::REGEX_FAILED_ERROR]);
      $violations = iterator_to_array($violations);
      $this->assertEmpty($violations);
    }
  }

  /**
   * Tests the short ID field and possible values.
   *
   * @dataProvider shortIdAllowedValuesProvider
   */
  public function testShortIdAllowedValues(string $short_id, bool $violation_expected): void {
    foreach (['collection', 'solution'] as $bundle) {
      $entity = Rdf::create([
        'rid' => $bundle,
        'field_short_id' => $short_id,
      ]);;

      $violations = $entity->validate()->findByCodes([RegexConstraint::REGEX_FAILED_ERROR]);
      $violations = iterator_to_array($violations);

      $this->assertCount((int) $violation_expected, $violations);
    }
  }

  /**
   * Data provider for ::testShortIdAllowedValues().
   *
   * @return array
   *   The test cases. Each test case contains the possible string and TRUE if
   *   the string triggers a validation error, or FALSE if the string does not
   *   cause a validation error.
   */
  public function shortIdAllowedValuesProvider(): array {
    return [
      'starts with dash' => ['-StartWithDash', FALSE],
      'ends with dash' => ['EndWithDash-', FALSE],
      'contains space' => ['Contains Space', TRUE],
      'contains special character' => ['SpecialCharacter!', TRUE],
      'too short 1 character' => ['A', TRUE],
      'too short 2 characters' => ['Aa', TRUE],
      'too short 3 characters' => ['Aaa', TRUE],
      'valid only characters' => ['AaaB', FALSE],
      'valid with dash' => ['Aa-aB', FALSE],
      'valid with number' => ['Aa-aB1', FALSE],
      'valid 26 characters long' => ['abcdefghijklmnopqrstuvwxyz', FALSE],
      'too long 27 characters long' => ['abcdefghijklmnopqrstuvwxyz0', TRUE],
    ];
  }

}
