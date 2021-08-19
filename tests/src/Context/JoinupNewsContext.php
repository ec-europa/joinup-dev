<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\DrupalExtension\Hook\Scope\BeforeNodeCreateScope;
use Drupal\joinup\Traits\EntityTrait;
use Drupal\joinup\Traits\NodeTrait;
use PHPUnit\Framework\Assert;

/**
 * Behat step definitions for testing news pages.
 */
class JoinupNewsContext extends RawDrupalContext {

  use EntityTrait;
  use NodeTrait;

  /**
   * Navigates to the canonical page display of a news page.
   *
   * @param string $title
   *   The name of the news page.
   *
   * @When I go to the :title news
   * @When I visit the :title news
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function visitNewsPage(string $title): void {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->getNodeByTitle($title, 'news');
    $this->visitPath($node->toUrl()->toString());
  }

  /**
   * Checks the contents of the latest news section.
   *
   * This also checks if the news articles are in the correct order and if the
   * total number of articles matches the expected number.
   *
   * Table format:
   * | date   | topics            | title      | body      |
   * | 28 Feb | HR, Finance in EU | News title | Body text |
   *
   * @param \Behat\Gherkin\Node\TableNode $table
   *   A table containing the expected content of the latest news section.
   *
   * @Then the latest news section should contain the following news articles:
   */
  public function assertLatestNews(TableNode $table) {
    $columns = $table->getColumnsHash();
    $articles = $this->getSession()->getPage()->findAll('css', '.view-latest-news article');
    Assert::assertEquals(count($columns), count($articles), sprintf('Expected %d articles in the "Latest news" section but found %d articles', count($columns), count($articles)));

    foreach ($columns as $i => $expected_data) {
      $actual_data = array_shift($articles);

      // Check title text.
      $actual_title = $actual_data->find('css', 'h2')->getText();
      Assert::assertEquals($expected_data['title'], $actual_title, sprintf('Expected title "%s" for article %d in the "Latest news" section but instead found "%s".', $expected_data['title'], $i + 1, $actual_title));

      // Check that title links to the canonical page of the news article.
      $news_node = self::getNodeByTitle($actual_title, 'news');
      $xpath = '//h2/a[@href = "' . $news_node->toUrl()->toString() . '"]';
      Assert::assertNotEmpty($actual_data->find('xpath', $xpath), sprintf('Article "%s" does not link to the canonical news page.', $actual_title));

      // Check the date.
      $actual_date = $actual_data->find('css', '.dated-listing--date')->getText();
      Assert::assertEquals($expected_data['date'], $actual_date, sprintf('Expected date "%s" for the "%s" article in the "Latest news" section but found "%s" instead.', $expected_data['date'], $actual_title, $actual_date));

      // Check that the correct number of topics are present.
      $expected_topic_titles = array_map('trim', explode(',', $expected_data['topics']));
      $topic_elements = $actual_data->findAll('css', '.field--name-field-topic .field__item');
      Assert::assertEquals(count($expected_topic_titles), count($topic_elements), sprintf('Expected %d topics for the "%s" article in the "Latest news" section but found %d topics.', count($expected_topic_titles), $expected_data['title'], count($topic_elements)));

      // Check the body text. It should not contain any HTML tags.
      $actual_body = $actual_data->find('css', '.field--name-body')->getHtml();
      Assert::assertEquals(strip_tags($actual_body), $actual_body, sprintf('The body text for the article "%s" in the "Latest news" section should have all HTML tags stripped.', $actual_title));
      Assert::assertEquals($expected_data['body'], $actual_body, sprintf('The body text for the article "%s" in the "Latest news" section does not contain the expected text.', $actual_title));

      foreach ($expected_topic_titles as $j => $expected_topic_title) {
        /** @var \Behat\Mink\Element\NodeElement $topic_element */
        $topic_element = array_shift($topic_elements);

        // Check the title of each topic.
        $actual_topic_title = $topic_element->getText();
        Assert::assertEquals($expected_topic_title, $actual_topic_title, sprintf('Expected topic #%d to be "%s" for the "%s" article in the "Latest news" section but instead found "%s".', $j + 1, $expected_topic_title, $actual_title, $actual_topic_title));

        // Check that each topic links to their canonical page.
        $topic_entity = self::getEntityByLabel('taxonomy_term', $actual_topic_title, 'topic');
        $xpath = '/a[@href = "' . $topic_entity->toUrl()->toString() . '"]';
        Assert::assertNotEmpty($topic_element->find('xpath', $xpath), sprintf('Topic "%s" for article "%s" does not link to the canonical topic page.', $actual_topic_title, $actual_title));
      }
    }
  }

  /**
   * Provides default values for required fields.
   *
   * @param \Drupal\DrupalExtension\Hook\Scope\BeforeNodeCreateScope $scope
   *   An object containing the entity properties and fields that are to be used
   *   for creating the node as properties on the object.
   *
   * @BeforeNodeCreate
   */
  public static function massageNewsFieldsBeforeNodeCreate(BeforeNodeCreateScope $scope): void {
    $node = $scope->getEntity();

    if ($node->type !== 'news') {
      return;
    }

    // The Headline field is required, and in normal usage a news article cannot
    // be created without one. Provide a default value if the scenario omits it.
    if (empty($node->field_news_headline)) {
      $node->field_news_headline = sprintf('Top %d interoperability tips. You will never believe what is on number %d!', rand(0, 100000), rand(0, 10));
    }
  }

}
