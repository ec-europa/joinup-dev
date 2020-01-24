<?php

/**
 * @file
 * Contains \JoinupSeoSubContext.
 */

declare(strict_types = 1);

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use PHPUnit\Framework\Assert;

/**
 * Behat step definitions for testing SEO.
 */
class JoinupSeoSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  /**
   * Asserts a meta tag value in the page.
   *
   * @param string $meta_name
   *   The meta tag name.
   * @param string $meta_value
   *   The meta tag value.
   *
   * @throws \Exception
   *   Thrown when an tag is not found or the value is not correct.
   *
   * @Given the :meta_name metatag should be set to :meta_value
   */
  public function assertMetatagInPage(string $meta_name, string $meta_value): void {
    $xpath = "//head/meta[@name=\"{$meta_name}\" and @content=\"{$meta_value}\"]";
    if (empty($this->getSession()->getPage()->find('xpath', $xpath))) {
      throw new Exception("The meta property '{$meta_name}' was either not found or the value is not set to '{$meta_value}'");
    }
  }

  /**
   * Asserts that the schema.org metatags are attached in page.
   *
   * @Given the metatag JSON should be attached in the page
   */
  public function assertJsonMetatagsInPage(): void {
    $json = $this->getMetatagsAsJson();
    Assert::assertNotEmpty($json, 'Entity metadata are found in the table.');
    // Assert that the context of the metatag is 'schema.org' to ensure that the
    // correct metatag is loaded and exists.
    Assert::assertArrayHasKey('@context', $json, '@context key not found in the metatag array.');
    Assert::assertEquals($json['@context'], 'https://schema.org', 'The @context property value is not set to the appropriate url.');
  }

  /**
   * Asserts that the schema.org metatags are attached in page.
   *
   * @Given the metatag JSON should not be attached in the page
   */
  public function assertMetatagsNotInPage(): void {
    $json = $this->getMetatagsAsJson();
    Assert::assertEmpty($json, 'Entity metadata are not attached in the page.');
  }

  /**
   * Asserts the amount of entity graphs of a certain type.
   *
   * @param int $count
   *   The number of graphs of the given type.
   * @param string $type
   *   The type of graph which is the schema class.
   *
   * @Given :count metatag graph of type :type should exist in the page
   */
  public function assertNumberOfEntityGraphsExist(int $count, string $type): void {
    $json = $this->getMetatagsAsJson();
    $found = 0;
    foreach ($json['@graph'] as $graph) {
      $graph = (array) $graph;
      if ($graph['@type'] === $type) {
        $found++;
      }
    }
    Assert::assertEquals((int) $count, $found, "{$count} graphs of type {$type} were expected. {$found} were found.");
  }

  /**
   * Asserts a list of properties in the graph identified by a property.
   *
   * @param string $property
   *   The property of which to identify the graph by.
   * @param string $value
   *   The value of the property of which to identify the graph by.
   * @param \Behat\Gherkin\Node\TableNode $table
   *   A table of properties for the given graph.
   * @param string $sub_property
   *   A sub property to look into.
   *
   * @throws \Exception
   *   Thrown if the graph is not found in the page or the requested sub
   *   property does not exist.
   *
   * @Given the metatag graph of the item with :property :value should have the following properties:
   * @Given the metatag graph of the item with :property :value should have the following :sub_property properties:
   */
  public function assertPropertiesOfMetatagGraph(string $property, string $value, TableNode $table, string $sub_property = ''): void {
    $graph = $this->getGraphFromMetatags($property, $value);
    if (empty($graph)) {
      throw new \Exception("No graphs were found that have a property '{$property}' of value '{$value}'.");
    }
    if (!empty($sub_property) && !isset($graph[$sub_property])) {
      throw new \Exception("The graph with property '{$property}' of value '{$value}' does not have a sub property '{$sub_property}'.");
    }

    // The url structure is very important in the SEO metatags as a wrong url
    // or an internal path could cause search engines to be missleaded. Thus,
    // the full url must be always asserted.
    $base_url = \Drupal::request()->getSchemeAndHttpHost();

    if (!empty($sub_property)) {
      $graph = (array) $graph[$sub_property];
    }

    $not_exists = [];
    $wrong_value = [];
    foreach ($table->getColumnsHash() as $row) {
      if (!isset($graph[$row['property']])) {
        $not_exists[] = $row['property'];
      }

      $row['value'] = str_replace('$base_url$', $base_url, $row['value']);
      if ($graph[$row['property']] !== $row['value']) {
        $wrong_value[$row['property']] = [
          'expected' => $row['value'],
          'found' => $graph[$row['property']],
        ];
      }
    }

    if (!empty($not_exists) || !empty($wrong_value)) {
      $error_messages = [];
      foreach ($not_exists as $property) {
        $error_messages[] = "The property '{$property}' was not found in the graph.";
      }
      foreach ($wrong_value as $property => $data) {
        $error_messages[] = "The property '{$property}' had an expected value '{$data['expected']}' but '{$data['found']}' was found.";
      }

      throw new Exception(implode("\n", $error_messages));
    }
  }

  /**
   * Retrieves the entity metatags as a JSON array from the page.
   *
   * @return array|null
   *   The SEO metatags as an array or null if not found.
   */
  protected function getMetatagsAsJson(): ?array {
    $page = $this->getSession()->getPage();
    if ($script = $page->find('xpath', '//script[@type="application/ld+json"]')) {
      $json = $script->getText();
      return (array) json_decode($json);
    }

    return NULL;
  }

  /**
   * Searches the metatag array for the first graph that matches the criteria.
   *
   * @param string $property
   *   The property of which to identify the graph by.
   * @param string $value
   *   The value of the property of which to identify the graph by.
   *
   * @return array|null
   *   The first graph that matches the criteria as an array or null if no match
   *   is found.
   */
  protected function getGraphFromMetatags(string $property, string $value): ?array {
    $json = $this->getMetatagsAsJson();
    foreach ($json['@graph'] as $graph) {
      if (isset($graph->$property) && $graph->$property === $value) {
        return (array) $graph;
      }
    }

    return NULL;
  }

}
