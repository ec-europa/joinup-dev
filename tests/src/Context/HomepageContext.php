<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\joinup\Traits\EntityTrait;
use Drupal\joinup\Traits\NodeTrait;
use PHPUnit\Framework\Assert;

/**
 * Behat step definitions to interact with elements on the Joinup homepage.
 */
class HomepageContext extends RawDrupalContext {

  use EntityTrait;
  use NodeTrait;

  /**
   * Checks the contents of the "Explore" block.
   *
   * This is showing a number of content items (collections, solutions, news and
   * events) on the homepage.
   *
   * This also checks if the content is in the correct order and if the total
   * number of content items is correct.
   *
   * @codingStandardsIgnoreStart
   * Table format:
   *  | type       | title             | date                   | description              |
   *  | solution   | Cities of Italy   | 2020-01-01 17:36 +0200 | Sum is therefore         |
   *  | collection | Products of Italy | 2019-08-14 17:36 +0200 | Lorem Ipsum is therefore |
   * @codingStandardsIgnoreEnd
   *
   * @param \Behat\Gherkin\Node\TableNode $table
   *   A table containing the expected content of the explore section.
   *
   * @Then the explore section should contain the following content:
   */
  public function assertExplore(TableNode $table) {
    $columns = $table->getColumnsHash();
    $articles = $this->getSession()->getPage()->findAll('css', '.block-joinup-front-page-explore-block article');
    Assert::assertEquals(count($columns), count($articles), sprintf('Expected %d items in the "Explore" section but found %d items.', count($columns), count($articles)));

    foreach ($columns as $expected_data) {
      $actual_data = array_shift($articles);
      $type = $expected_data['type'];

      if (in_array($type, ['collection', 'solution'])) {
        $entity = self::getEntityByLabel('rdf_entity', $expected_data['title']);
      }
      else {
        $entity = self::getNodeByTitle($expected_data['title']);
      }

      // Check that title links to the canonical page of the
      // news, event, solution and collection.
      $xpath = '//h2/a[@href = "' . $entity->toUrl()->toString() . '" and contains(., "' . $expected_data['title'] . '")]';
      Assert::assertNotEmpty($actual_data->find('xpath', $xpath), sprintf('%s "%s" does not have the correct title which links to the canonical page.', $type, $expected_data['title']));

      // Check the body/description text.
      $description_element = $actual_data->find('css', '.explore-item__description div');
      Assert::assertInstanceOf(NodeElement::class, $description_element, sprintf('Did not find a description for the %s "%s" in the "Explore" section.', $type, $expected_data['title']));

      $actual_description = $description_element->getHtml();
      $xpath = '//*[text() = "' . $expected_data['description'] . '"]';
      Assert::assertEquals($expected_data['description'], $actual_description, sprintf('The body text for the %s "%s" in the "Explore" section does not contain the expected text.', $type, $expected_data['title']));

      // Check date.
      $expected_date = date("d/m/Y", strtotime($expected_data['date']));
      $xpath = '//*[text() = "' . $expected_date . '"]';
      Assert::assertNotEmpty($actual_data->find('xpath', $xpath), sprintf('The date for the %s "%s" in the "Explore" section does not contain the expected format.', $type, $expected_data['title']));
    }
  }

}
