<?php

/**
 * @file
 * Contains \Isa2AnalyticsSubContext.
 */

declare(strict_types = 1);

use Behat\Mink\Element\NodeElement;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\oe_webtools_analytics\AnalyticsEventInterface;
use PHPUnit\Framework\Assert;

/**
 * Behat step definitions for testing visitor analytics.
 */
class Isa2AnalyticsSubContext extends DrupalSubContextBase {

  /**
   * Checks the presence of a site section in the analytics reporting data.
   *
   * @param string $section
   *   Optional site section to check. If omitted the site section is expected
   *   to be missing from the analytics data.
   *
   * @Then the analytics report should not include a site section
   * @Then the analytics report should include the site section :section
   */
  public function assertSiteSection(string $section = NULL): void {
    if ($section) {
      $this->assertAnalyticsJsonContainsParameter(AnalyticsEventInterface::SITE_SECTION, $section);
    }
    else {
      $this->assertAnalyticsJsonNotContainsParameter(AnalyticsEventInterface::SITE_SECTION);
    }
  }

  /**
   * Asserts that a given parameter is passed in the analytics data.
   *
   * @param string $parameter
   *   The parameter name.
   * @param string $value
   *   The expected value for the parameter.
   */
  protected function assertAnalyticsJsonContainsParameter(string $parameter, string $value): void {
    $analytics_data = $this->getAnalyticsData();
    Assert::assertArrayHasKey($parameter, $analytics_data);
    Assert::assertEquals($value, $analytics_data[$parameter]);
  }

  /**
   * Asserts that a given parameter is not passed in the analytics data.
   *
   * @param string $parameter
   *   The parameter that should not be present in the analytics data.
   */
  protected function assertAnalyticsJsonNotContainsParameter(string $parameter): void {
    $analytics_data = $this->getAnalyticsData();
    Assert::assertArrayNotHasKey($parameter, $analytics_data);
  }

  /**
   * Returns the data being reported to Webtools Analytics.
   *
   * @return array
   *   The data being reported to Webtools Analytics.
   */
  protected function getAnalyticsData(): array {
    // The Webtools Analytics data is present in the form of JSON data. Retrieve
    // all JSON data from the page.
    $elements = $this->getSession()->getPage()->findAll("css", "script[type=\"application/json\"]");
    $json_applications = array_map(function (NodeElement $element) {
      return json_decode($element->getText(), TRUE);
    }, $elements);

    // The analytics data is a JSON array that can be identified by the 'piwik'
    // value in the 'utility' key.
    $analytics_data_arrays = array_filter($json_applications, function ($json_data) {
      return is_array($json_data) && isset($json_data['utility']) && $json_data['utility'] === 'piwik';
    });

    // Fail if there are multiple analytics JSON arrays found.
    Assert::assertCount(1, $analytics_data_arrays, 'There should be exactly 1 analytics JSON array in the page.');

    return reset($analytics_data_arrays);
  }

}
