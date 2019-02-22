<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\Component\Utility\Html;
use PHPUnit\Framework\Assert;

/**
 * Provides steps definitions for interacting with RSS feeds.
 */
class RssContext extends RawMinkContext {

  /**
   * The current feed being analysed.
   *
   * @var \DOMDocument
   */
  protected $rss;

  /**
   * Asserts that the current page response contains a valid RSS feed.
   *
   * @Then I (should )see a valid RSS feed
   */
  public function assertRss(): void {
    libxml_use_internal_errors(TRUE);
    libxml_clear_errors();

    $rss = new \DOMDocument();

    if ($rss->loadXML($this->getSession()->getPage()->getContent()) === FALSE) {
      throw new \Exception('The page does not contain a valid RSS feed.');
    }

    if ($rss->schemaValidate(__DIR__ . '/../../schemas/RSS20.xsd') === FALSE) {
      $message = "The RSS feed does not comply with the RSS schema.\n";
      foreach (libxml_get_errors() as $error) {
        $message .= sprintf('Error at line %s, column %s: %s', $error->line, $error->column, $error->message);
      }
      libxml_clear_errors();
      throw new \Exception($message);
    }

    $this->rss = $rss;
  }

  /**
   * Verifies the elements for the channel tag.
   *
   * Table format:
   * | title       | link               | description         | ... |
   * | Joinup feed | /collection/joinup | RSS feed for Joinup | ... |
   *
   * @param \Behat\Gherkin\Node\TableNode $table
   *   The channel elements data.
   *
   * @Then the RSS feed channel elements should be:
   */
  public function assertRssChannelElements(TableNode $table): void {
    $this->assertRssLoaded();

    $elements = $table->getRowsHash();
    // Convert relative urls to absolute.
    if (isset($elements['link'])) {
      $elements['link'] = $this->locatePath($elements['link']);
    }

    $xpath = $this->getXpathInstance();
    foreach ($elements as $key => $value) {
      $nodes = $xpath->query('//channel/' . $key);
      // Support is limited to nodes that appear once only.
      Assert::assertCount(1, $nodes, "Invalid count for $key element.");
      Assert::assertEquals($value, trim($nodes->item(0)->nodeValue));
    }
  }

  /**
   * Asserts the number of items present in the current RSS feed.
   *
   * @param int $count
   *   The expected number of items.
   *
   * @Then the RSS feed should have :count items
   */
  public function assertRssItemsCount(int $count): void {
    $this->assertRssLoaded();

    $items = $this->getXpathInstance()->query('//channel/item');
    Assert::assertCount($count, $items);
  }

  /**
   * Asserts the items present in the current RSS feed.
   *
   * @codingStandardsIgnoreStart
   * Table format:
   * | title     | link            | description          | publication date                | author        | ... |
   * | News item | /news/news-item | Content of the news. | Thu, 24 Jan 2019 14:08:09 +0100 | Joinup editor | ... |
   * @codingStandardsIgnoreEnd
   *
   * @param \Behat\Gherkin\Node\TableNode $table
   *   The item data.
   *
   * @Then the RSS feed items should be:
   */
  public function assertRssItems(TableNode $table): void {
    $this->assertRssLoaded();
    $this->assertRssItemsCount(count($table->getColumnsHash()));

    $xpath = $this->getXpathInstance();
    foreach ($table->getColumnsHash() as $index => $item) {
      $item = $this->massageRssItemData($item);

      // Xpath is 1-index based.
      $xpath_node_index = $index + 1;
      foreach ($item as $key => $value) {
        $nodes = $xpath->query("//channel/item[$xpath_node_index]/$key");
        // Support is limited to nodes that appear once only.
        Assert::assertCount(1, $nodes, "Invalid count for $key element.");
        Assert::assertEquals($value, trim($nodes->item(0)->nodeValue));
      }
    }
  }

  /**
   * Verifies that an RSS has been loaded through one of the steps.
   */
  protected function assertRssLoaded(): void {
    if ($this->rss === NULL) {
      throw new \Exception('No RSS feed has been extracted yet.');
    }
  }

  /**
   * Returns a new DOMXPath instance for the current RSS document.
   *
   * @return \DOMXPath
   *   The xpath instance.
   */
  protected function getXpathInstance(): \DOMXPath {
    return new \DOMXPath($this->rss);
  }

  /**
   * Massages RSS item data coming from Behat steps.
   *
   * @param array $item
   *   The RSS item data.
   *
   * @return array
   *   The massaged item data.
   */
  protected function massageRssItemData(array $item): array {
    // Translate aliases back to machine names.
    $aliases = [
      'publication date' => 'pubDate',
      'author' => 'dc:creator',
    ];

    foreach ($aliases as $key => $real_name) {
      if (array_key_exists($key, $item)) {
        $item[$real_name] = $item[$key];
        unset($item[$key]);
      }
    }

    // Convert relative urls to absolute.
    if (isset($item['link'])) {
      $item['link'] = $this->locatePath($item['link']);
    }

    return $item;
  }

  /**
   * Asserts that an RSS autodiscovery link is present in the page header.
   *
   * @param string $stitle
   *   The title of the RSS feed link.
   * @param string $href
   *   The relative or absolute url where the RSS feed link points.
   *
   * @Then the page( should) contain(s) an RSS( autodiscovery) link with title :title pointing to :href
   */
  public function assertRssAutodiscoveryLinkPresent(string $stitle, string $href): void {
    $xpath = sprintf(
      '//head/link[@rel="alternate"][@type="application/rss+xml"][@title="%s"][@href="%s" or @href="%s"]',
      Html::escape($stitle),
      $href,
      $this->locatePath($href)
    );

    $nodes = $this->getSession()->getPage()->findAll('xpath', $xpath);
    Assert::assertCount(1, $nodes);
  }

  /**
   * Asserts the number of RSS autodiscovery links present in the page.
   *
   * @param int $count
   *   The number of RSS autodiscovery links.
   *
   * @Then the page should contain :count RSS autodiscovery link(s)
   */
  public function assertRssAutodiscoveryLinkCount(int $count): void {
    $xpath = '//head/link[@rel="alternate"][@type="application/rss+xml"]';
    Assert::assertCount($count, $this->getSession()->getPage()->findAll('xpath', $xpath));
  }

}
