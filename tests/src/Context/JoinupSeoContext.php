<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\rdf_entity\RdfInterface;
use PHPUnit\Framework\Assert;

/**
 * Behat step definitions for testing SEO.
 */
class JoinupSeoContext extends RawDrupalContext {

  use RdfEntityTrait;

  /**
   * Asserts that a list of meta tags are presented in the page.
   *
   * @codingStandardsIgnoreStart
   * | identifier            | value            |
   * | description           | Some description |
   * | og:title              | My site          |
   * @codingStandardsIgnoreEnd
   * The "identifier" and the "meta value" table headers are mandatory. The
   * "identifier" can be either the name or the property value of the meta
   * entry.
   *
   * @param \Behat\Gherkin\Node\TableNode $list
   *   A list of meta tags to check for.
   *
   * @throws \Exception
   *   Thrown if at least one of the tags is not found or has a wrong value.
   *
   * @Given the following meta tags should available in the html:
   */
  public function assertMetaTagListInPage(TableNode $list): void {
    $page = $this->getSession()->getPage();
    $missing = [];
    $errors = [];
    foreach ($list->getColumnsHash() as $row) {
      $identifier = $row['identifier'];
      $xpath = "//head/meta[@name='{$identifier}' or @property='{$identifier}']/@content";
      $tag = $page->find('xpath', $xpath);
      if (empty($tag)) {
        $missing[$identifier] = $identifier;
      }
      else {
        $row['value'] = $this->escapeStringWithVariables($row['value']);
        $actual = $tag->getText();
        $found = preg_match("#^{$row['value']}$#", $actual) === 1;

        if ($found === FALSE) {
          $errors[] = [
            'property' => $identifier,
            'expected' => $row['value'],
            'found' => $actual,
          ];
        }
      }
    }

    // Construct the error messages for each graph found.
    $error_messages = [];
    if (!empty($missing)) {
      $properties = implode(', ', $missing);
      $error_messages[] = "Meta entries not found: {$properties}";
    }
    foreach ($errors as $error) {
      $error_messages[] = "Wrong property '{$error['property']}' value: Expected '{$error['expected']}' but '{$error['found']}' was found.";
    }

    if (!empty($error_messages)) {
      throw new \Exception(implode("\n", $error_messages));
    }
  }

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
      throw new \Exception("The meta property '{$meta_name}' was either not found or the value is not set to '{$meta_value}'");
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
   * @param string|null $sub_property
   *   A sub property to look into.
   * @param int|null $delta
   *   If the field contains multiple values, i.e. the pivot is enabled, then
   *   use the $delta variable to check the specific delta.
   *
   * @throws \Exception
   *   Thrown if the graph is not found in the page or the requested sub
   *   property does not exist.
   *
   * @Given the metatag (sub)graph of the item with :property :value should have the following properties:
   * @Given the metatag (sub)graph of the item with :property :value should have the following :sub_property properties:
   * @Given the metatag (sub)graph of the item with :property :value should have the following properties in index :delta:
   * @Given the metatag (sub)graph of the item with :property :value should have the following :sub_property properties in index :delta:
   */
  public function assertPropertiesOfMetatagGraph(string $property, string $value, TableNode $table, ?string $sub_property = NULL, $delta = NULL): void {
    $graphs = $this->getGraphsFromMetatags($property, $value, $sub_property);
    if (empty($graphs)) {
      throw new \Exception("No graphs were found that have a property '{$property}' of value '{$value}' or none of them contain a '{$sub_property}' property.");
    }

    $hash = $table->getColumnsHash();
    $missing_properties = [];
    $wrong_value = [];
    foreach ($graphs as $index => $graph) {
      if ($delta !== NULL) {
        $graph = $graph[$delta];
      }
      $found = TRUE;
      foreach ($hash as $row) {
        if (!isset($graph[$row['property']])) {
          $missing_properties[$index][] = $row['property'];
          $found = FALSE;
          continue;
        }

        $row['value'] = $this->escapeStringWithVariables($row['value']);
        $found = preg_match("#^{$row['value']}$#", $graph[$row['property']]) === 1;

        if ($found === FALSE) {
          $wrong_value[$index][] = [
            'property' => $row['property'],
            'expected' => $row['value'],
            'found' => $graph[$row['property']],
          ];
        }
      }

      if ($found === TRUE) {
        break;
      }
    }

    if (empty($wrong_value) && empty($missing_properties)) {
      return;
    }

    // Construct the error messages for each graph found.
    $error_messages = [];
    foreach ($graphs as $index => $graph) {
      if (!empty($missing_properties[$index])) {
        $properties = implode(', ', $missing_properties[$index]);
        $error_messages[] = "Index {$index}: The graph is missing the following properties: {$properties}";
      }
      if (isset($wrong_value[$index])) {
        foreach ($wrong_value[$index] as $data) {
          $error_messages[] = "Index {$index}: Wrong property '{$data['property']}' value: Expected '{$data['expected']}' but '{$data['found']}' was found.";
        }
      }
    }
    throw new \Exception(implode("\n", $error_messages));
  }

  /**
   * Escapes available variables from string and prepares it for a regex match.
   *
   * @param string $string
   *   The string to convert.
   *
   * @return string
   *   The converted string.
   */
  protected function escapeStringWithVariables(string $string): string {
    // The URL structure is very important in the SEO metatags as a wrong URL
    // or an internal path could cause search engines to be misled. Thus,
    // the full URL must be always asserted.
    $base_url = $this->getMinkParameter('base_url');

    $string = preg_quote($string, '#');
    $replacements = [
      '__base_url__' => preg_quote($base_url),
      '__random_text__' => '([^/]*)?',
      '__timezone__' => '\d{2}',
    ];

    return strtr($string, $replacements);
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
      return json_decode($json, TRUE);
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
   * @param string|null $sub_graph
   *   (optional) Fetch a subgraph of the graphs instead of the graph itself.
   *
   * @return array|null
   *   The first graph that matches the criteria as an array or null if no match
   *   is found.
   */
  protected function getGraphsFromMetatags(string $property, string $value, ?string $sub_graph = NULL): ?array {
    $json = $this->getMetatagsAsJson();
    $sub_graphs = $this->getSubGraphsFromGraph($json, $property, $value);
    if (!empty($sub_graph)) {
      $sub_graphs = array_map(function (array $graph) use ($sub_graph) {
        return array_key_exists($sub_graph, $graph) ? $graph[$sub_graph] : NULL;
      }, $sub_graphs);
    }
    return array_filter($sub_graphs);
  }

  /**
   * Retrieves a list of subgraphs from a graph.
   *
   * Used to search for a property within the graph that contains a subproperty.
   * For example, a graph contains a location property which in turn contains a
   * sub property that has a latitude and a longitude. This method can search
   * either simply by the identifier or if the identifier contains a property or
   * if the property contained by an identifier matches a value.
   *
   * @param array $graph
   *   The parent graph.
   * @param string $identifier
   *   The property name to identify the graph with.
   * @param string|null $identifier_value
   *   (optional) The identifier value. Should not be used along with $property
   *   filter as the $identifier_value requires $identifier to have a string
   *   value, while $property requires it to have an array as a value.
   * @param bool $reset
   *   Reset the list of graphs. This method might run multiple times over many
   *   scenarios so the static variable $graphs_found might leak results in
   *   other cases. Initial value must always be TRUE while every subsequent
   *   call from within the method will be FALSE.
   *
   * @return array
   *   An array of subgraphs that match the above criteria.
   */
  protected function getSubGraphsFromGraph(array $graph, string $identifier, ?string $identifier_value = NULL, bool $reset = TRUE): array {
    static $graphs_found = [];
    if ($reset) {
      $graphs_found = [];
    }
    foreach ($graph as $sub_graph_or_value) {
      if (is_array($sub_graph_or_value)) {
        if (array_key_exists($identifier, $sub_graph_or_value)) {
          if (is_array($sub_graph_or_value[$identifier])) {
            if (empty($property)) {
              // No filter is required on the subgraph so the whole graph is
              // valid.
              $graphs_found[] = $sub_graph_or_value;
            }
          }
          // A non strict comparison is used because in behat, parameter values
          // are usually strings. Values can still be integers though.
          elseif (!empty($identifier_value) && $sub_graph_or_value[$identifier] == $identifier_value) {
            $graphs_found[] = $sub_graph_or_value;
          }
        }
        // In any case, the subgraph is an array so search deeper for valid
        // instances of subgraphs.
        $this->getSubGraphsFromGraph($sub_graph_or_value, $identifier, $identifier_value, FALSE);
      }
    }
    return $graphs_found;
  }

  /**
   * Asserts that the full metadata of the entity are attached in the page.
   *
   * @param string $title
   *   The rdf entity title.
   *
   * @throws \Exception
   *   Thrown when the entity is not found or the metadata are not displayed in
   *   the page.
   *
   * @Then the rdf metadata of the :title rdf entity should be attached in the page
   */
  public function assertSerializedRdfMetadataInPage(string $title): void {
    $rdf_entity = $this->getRdfEntityByLabel($title);
    $json_output = $this->generateSerializedData($rdf_entity);
    $this->assertSession()->responseContains($json_output);
  }

  /**
   * Asserts that the full metadata of the entity are not attached in the page.
   *
   * @param string $title
   *   The rdf entity title.
   *
   * @throws \Exception
   *   Thrown when the entity is not found or the metadata are not displayed in
   *   the page.
   *
   * @Then the rdf metadata of the :title rdf entity should not be attached in the page
   */
  public function assertSerializedRdfMetadataNotInPage(string $title): void {
    $rdf_entity = $this->getRdfEntityByLabel($title);
    $json_output = $this->generateSerializedData($rdf_entity);
    $this->assertSession()->responseNotContains($json_output);
  }

  /**
   * Adds a site verification entry.
   *
   * @codingStandardsIgnoreStart
   * | engine         | google              |
   * | File           | site_abcdefg        |
   * | File contents  | site_verify:abcdefg |
   * @codingStandardsIgnoreEnd
   *
   * @param \Behat\Gherkin\Node\TableNode $data
   *   The site verification data.
   *
   * @Given the following site verification:
   */
  public function givenSiteVerificationData(TableNode $data): void {
    $data = $data->getRowsHash();
    \Drupal::database()->insert('site_verify')
      ->fields([
        'engine' => $data['Engine'],
        'file' => $data['File'],
        'file_contents' => $data['File contents'],
        'meta' => '',
      ])
      ->execute();
    // The site_verify module rebuilds the routes whenever a new entry is added
    // in their settings form.
    \Drupal::getContainer()->get('router.builder')->rebuild();
  }

  /**
   * Generates the serialized metadata for the RDF entity.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The RDF entity.
   *
   * @return string
   *   The serialized metadata.
   */
  protected function generateSerializedData(RdfInterface $rdf_entity): string {
    /** @var \Drupal\joinup_seo\JoinupSeoExportHelperInterface $serializer */
    $serializer = \Drupal::service('joinup_seo.export_helper');
    return $serializer->exportRdfEntityMetadata($rdf_entity);
  }

}
