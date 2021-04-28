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
   * | date   | policy domains    | title      | body      |
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

      // Check that the correct number of policy domains are present.
      $expected_policy_domain_titles = array_map('trim', explode(',', $expected_data['policy domains']));
      $policy_domain_elements = $actual_data->findAll('css', '.field--name-field-policy-domain .field__item');
      Assert::assertEquals(count($expected_policy_domain_titles), count($policy_domain_elements), sprintf('Expected %d policy domains for the "%s" article in the "Latest news" section but found %d policy domains.', count($expected_policy_domain_titles), $expected_data['title'], count($policy_domain_elements)));

      // Check the body text.
      $actual_body = $actual_data->find('css', '.field--name-body')->getText();
      Assert::assertEquals($expected_data['body'], $actual_body, sprintf('The body text for the article "%s" in the "Latest news" section does not contain the expected text.', $actual_title));

      foreach ($expected_policy_domain_titles as $j => $expected_policy_domain_title) {
        /** @var \Behat\Mink\Element\NodeElement $policy_domain_element */
        $policy_domain_element = array_shift($policy_domain_elements);

        // Check the title of each policy domain.
        $actual_policy_domain_title = $policy_domain_element->getText();
        Assert::assertEquals($expected_policy_domain_title, $actual_policy_domain_title, sprintf('Expected policy domain #%d to be "%s" for the "%s" article in the "Latest news" section but instead found "%s".', $j + 1, $expected_policy_domain_title, $actual_title, $actual_policy_domain_title));

        // Check that each policy domain links to their canonical page.
        $policy_domain_entity = self::getEntityByLabel('taxonomy_term', $actual_policy_domain_title, 'policy_domain');
        $xpath = '/a[@href = "' . $policy_domain_entity->toUrl()->toString() . '"]';
        Assert::assertNotEmpty($policy_domain_element->find('xpath', $xpath), sprintf('Policy domain "%s" for article "%s" does not link to the canonical policy domain page.', $actual_policy_domain_title, $actual_title));
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
