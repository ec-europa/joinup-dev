<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_group\ExistingSite;

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
      'starts with dash' => ['-startwithdash', FALSE],
      'starts with dash contains uppercase' => ['-StartWithDash', TRUE],
      'ends with dash' => ['endwithdash-', FALSE],
      'ends with dash contains uppercase' => ['EndWithDash-', TRUE],
      'contains space' => ['contains space', TRUE],
      'contains space and uppercase' => ['Contains Space', TRUE],
      'contains special character' => ['specialcharacter!', TRUE],
      'contains special character and uppercase' => ['SpecialCharacter!', TRUE],
      'too short 1 character' => ['a', TRUE],
      'too short 2 characters' => ['aa', TRUE],
      'too short 3 characters' => ['aaa', TRUE],
      'valid only characters' => ['aaab', FALSE],
      'valid with dash' => ['aa-ab', FALSE],
      'uppercase with dash' => ['aa-aB', TRUE],
      'valid with number' => ['aa-ab1', FALSE],
      'uppercase with number' => ['aA-ab1', TRUE],
      'valid 26 characters long' => ['abcdefghijklmnopqrstuvwxyz', FALSE],
      'too long 27 characters long' => ['abcdefghijklmnopqrstuvwxyz0', TRUE],
    ];
  }

}
