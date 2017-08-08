<?php

/**
 * @file
 * Contains \SearchSubContext.
 */

use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\TraversingTrait;
use Drupal\joinup\Traits\UtilityTrait;

/**
 * Behat step definitions for testing searches.
 */
class SearchSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use TraversingTrait;
  use UtilityTrait;

  /**
   * {@inheritdoc}
   */
  public function cleanUsers() {
    try {
      parent::cleanUsers();
    }
    catch (Exception $e) {
      throw new Exception($e->getMessage());
    }
  }

  /**
   * Checks that the user is on the search page.
   *
   * The search page currently doesn't have a title so our common practice of
   * checking the page title falls short here.
   *
   * @Then I should be on the search page
   */
  public function assertSearchPage() {
    $this->assertSession()->addressEquals($this->locatePath('/search'));
  }

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
   * Checks that the given tab facet is selected.
   *
   * @param string $type
   *   The text of the content tab that should be selected.
   *
   * @throws \Exception
   *   Thrown when the tab facet is not selected or not present in the page.
   *
   * @Then the :type content tab should be selected
   */
  public function assertContentTypeFacetSelected($type) {
    $xpath = "//a[contains(concat(' ', normalize-space(@class), ' '), ' tab--content-type ') and contains(concat(' ', normalize-space(@class), ' '), ' is-active ')]//div[contains(concat(' ', normalize-space(@class), ' '), ' tab__text--lower ')][normalize-space(string(.)) = '$type']";
    $element = $this->getSession()->getPage()->find('xpath', $xpath);
    \PHPUnit_Framework_Assert::assertNotEmpty($element);
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
    $labels = $this->explodeCommaSeparatedStepArgument($labels);

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

  /**
   * Clicks a facet item in an inline facet.
   *
   * @param string $link
   *   The link text of the link to click.
   * @param string $facet
   *   The facet alias.
   *
   * @When I click :link in the :facet inline facet
   */
  public function iClickAnInlineFacetItemLink($link, $facet) {
    $this->findFacetByAlias($facet)->clickLink($link);
  }

  /**
   * Asserts that an inline facet has a certain text (value) set as active.
   *
   * @param string $text
   *   The text that should be in the active element of the facet.
   * @param string $facet
   *   The inline facet to test.
   *
   * @throws \Exception
   *   Thrown when the active items element is not found.
   *
   * @Then :text should be selected in the :facet inline facet
   */
  public function assertInlineFacetActiveText($text, $facet) {
    $element = $this->findFacetByAlias($facet);

    $active = $element->find('css', '.filter__term');
    if (!$active) {
      throw new \Exception("Cannot find active items on the facet '$facet'.");
    }

    $active_text = trim($active->getText());
    if ($text !== $active_text) {
      throw new \Exception("Expected active elements to be '$text', but found '$active_text'.");
    }
  }

  /**
   * Asserts the inactive items on an inline facet.
   *
   * This function asserts also the order of the items.
   *
   * @param string $facet
   *   The facet alias.
   * @param string $values
   *   A comma-separated list of items to be present.
   *
   * @Then the :facet inline facet should allow selecting the following values :value
   */
  public function assertInlineFacetInactiveItems($facet, $values) {
    $element = $this->findFacetByAlias($facet);
    $found = array_map(function ($item) {
      /** @var \Behat\Mink\Element\NodeElement $item */
      return $item->getText();
    }, $element->findAll('css', 'ul.mdl-menu li.facet-item'));

    $values = $this->explodeCommaSeparatedStepArgument($values);
    \PHPUnit_Framework_Assert::assertEquals($values, $found, "The '{$facet}' values mismatch the expected ones.");
  }

  /**
   * Launches a search.
   *
   * @param string $keywords
   *   A list of words to search.
   *
   * @throws \Exception
   *   Thrown when the header search bar is not found.
   *
   * @When I enter :keywords in the header search bar and hit enter
   */
  public function launchSearchFromHeader($keywords) {
    // The header search form is not a full form, so it doesn't have a submit
    // button.
    // Sending keys is not supported by Behat\Mink\Driver\GoutteDriver , so we
    // cannot fake the pressing of the Enter key.
    // To keep speed, we provide a step that fakes the user input in the header
    // search bar and pressing the enter key. Simple tests can be written to
    // verify the form functionality if needed.
    $this->getSession()->visit($this->locatePath('/search?keys=' . $keywords));
  }

}
