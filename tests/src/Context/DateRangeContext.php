<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Behat\Mink\Element\NodeElement;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use PHPUnit\Framework\Assert;

/**
 * Provides step definitions regarding the search page date range fields.
 */
class DateRangeContext extends RawDrupalContext {

  /**
   * A list of human readable aliases for the date range filters.
   */
  const DATE_RANGE_ALIASES = [
    'Created date minimum' => 'edit-created-min',
    'Created date maximum' => 'edit-created-max',
    'Updated date minimum' => 'edit-updated-min',
    'Updated date maximum' => 'edit-updated-max',
  ];

  /**
   * Asserts that a date range field exists in the page.
   *
   * @param string $alias
   *   The alias of the date range field. One of self::DATE_RANGE_ALIASES.
   *
   * @Given I should see the :alias date range search filter
   */
  public function assertDateRangeFieldPresent(string $alias): void {
    $this->findDateRangeField($alias);
  }

  /**
   * Asserts that a date range field does not exists in the page.
   *
   * @param string $alias
   *   The alias of the date range field. One of self::DATE_RANGE_ALIASES.
   *
   * @Given I should not see the :alias date range search filter
   */
  public function assertDateRangeFieldNotPresent(string $alias): void {
    Assert::assertEmpty($this->findDateRangeField($alias, FALSE), "Date range '{$alias}' was found in the page but should not.");
  }

  /**
   * Asserts that a date range field does not exists in the page.
   *
   * @param string $alias
   *   The alias of the date range field. One of self::DATE_RANGE_ALIASES.
   * @param string $value
   *   The the value to fill in.
   *
   * @Given I fill in the :alias date range filter with :value
   */
  public function fillDateRangeFieldValue(string $alias, string $value): void {
    $field = $this->findDateRangeField($alias);
    try {
      $field->setValue($value);
    }
    catch (\Exception $e) {
      // When Selenium attempts to fill in a field, it attempts to un-focus the
      // field in order to trigger a change event. However, the date-range
      // fields are auto submitting. By the time the un-focus javascript is ran,
      // the page is already reloading and the element reports missing. Skip
      // this exception.
    }
  }

  /**
   * Find date range field by alias.
   *
   * @param string $alias
   *   The alias of the date range field. One of self::DATE_RANGE_ALIASES.
   * @param bool $strict
   *   When TRUE, an exception will be thrown if the field is not found.
   *
   *   return \Behat\Mink\Element\NodeElement|null
   *   The node element or NULL if none is found.
   */
  protected function findDateRangeField(string $alias, bool $strict = TRUE): ?NodeElement {
    Assert::assertArrayHasKey($alias, self::DATE_RANGE_ALIASES, "Alias {$alias} not in permitted aliases: " . implode(', ', self::DATE_RANGE_ALIASES));
    $selector = self::DATE_RANGE_ALIASES[$alias];
    $xpath = "//input[@type='date' and contains(concat(' ', @data-drupal-selector, ' '), ' {$selector} ')]";
    $elements = $this->getSession()->getPage()->findAll('xpath', $xpath);

    if ($strict) {
      Assert::assertNotEmpty($elements, "Date range '{$alias}' was not found in the page.");
    }

    if (count($elements) > 1) {
      throw new \LogicException("More than one {$alias} were found in the page.");
    }

    return empty($elements) ? NULL : reset($elements);
  }

}
