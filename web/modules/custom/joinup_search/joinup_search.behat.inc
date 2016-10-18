<?php

/**
 * @file
 * Contains \SearchSubContext.
 */

use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;

/**
 * Behat step definitions for testing searches.
 */
class SearchSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  /**
   * Click a specific tab facet in the page.
   *
   * @param string $type
   *   The text of the content tab.
   *
   * @throws \Exception
   *   Thrown when the tab is not found in the page.
   *
   * @When I click the :type content tab
   */
  public function clickContentTypeFacet($type) {
    // Find the div that contains the content type text and than traverse back
    // to the link wrapping it.
    $xpath = "//div[@class='tabs--content-type']//div[@class='tab__text--lower'][normalize-space(string(.)) = '$type']" .
      "/ancestor-or-self::a[@class and contains(concat(' ', normalize-space(@class), ' '), ' tab--content-type ')]";

    $link = $this->getSession()->getPage()->find('xpath', $xpath);

    if (!$link) {
      throw new \Exception("The tab $type was not found in the page.");
    }

    $link->click();
  }

  /**
   * Asserts that certain content type facet items are not shown on the page.
   *
   * @param string $labels
   *   A comma-separated list of facet item labels.
   *
   * @throws \Exception
   *   Thrown when an unwanted facet item is shown in the page.
   *
   * @When I should not see the following facet items :items
   */
  public function assertContentTypeFacetItemsNotPresent($labels) {
    // @todo Replace with trait function when ISAICP-2755 is in.
    $labels = explode(',', $labels);
    $labels = array_map('trim', $labels);
    $labels = array_filter($labels);

    $facet = $this->getSession()->getPage()->find('xpath', "//div[@data-drupal-facet-id='field_cp_content_listing_content_type']");
    $present = [];

    // Find all the items shown as tab.
    $elements = $facet->findAll('xpath', "//div[@class and contains(concat(' ', normalize-space(@class), ' '), ' tab__text--lower ')]");
    /** @var \Behat\Mink\Element\NodeElement $element */
    foreach ($elements as $element) {
      $present[] = $element->getText();
    }

    // Find all items shown in the list. The final "text()" is needed to skip
    // the item count.
    $xpath = "//li[@class and contains(concat(' ', normalize-space(@class), ' '), ' facet-item ')]/a/text()";
    $elements = $this->getSession()->getPage()->findAll('xpath', $xpath);
    foreach ($elements as $element) {
      $present[] = $element->getText();
    }

    $present = array_map('trim', $present);
    $found = array_intersect($labels, $present);

    if ($found) {
      throw new \Exception('Facet item(s) found, but should not be present: ' . implode(', ', $found));
    }
  }

}
