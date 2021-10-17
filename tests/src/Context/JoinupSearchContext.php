<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\joinup\Traits\BrowserCapabilityDetectionTrait;
use Drupal\joinup\Traits\KeyboardInteractionTrait;
use Drupal\joinup\Traits\MaterialDesignTrait;
use Drupal\joinup\Traits\TraversingTrait;
use Drupal\joinup\Traits\UtilityTrait;
use PHPUnit\Framework\Assert;

/**
 * Behat step definitions for testing searches.
 */
class JoinupSearchContext extends RawDrupalContext {

  use BrowserCapabilityDetectionTrait;
  use KeyboardInteractionTrait;
  use MaterialDesignTrait;
  use TraversingTrait;
  use UtilityTrait;

  /**
   * Navigates to the search page.
   *
   * @Given I am on the search( results)( page)
   * @When I visit the search( results)( page)
   */
  public function visitSearchPage() {
    $this->visitPath('/search');
  }

  /**
   * Checks that the user is on the search page.
   *
   * The search page currently doesn't have a title so our common practice of
   * checking the page title falls short here.
   *
   * @Then I should be on the (advanced )search page
   */
  public function assertSearchPage(): void {
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
  public function clickContentTypeFacet(string $type): void {
    $this->findContentTab($type)->click();
  }

  /**
   * Checks if a specific tab facet is displayed on the page.
   *
   * @param string $type
   *   The text of the content tab.
   *
   * @throws \Exception
   *   Thrown when the tab is not found in the page.
   *
   * @Given the :type content tab is displayed
   */
  public function assertContentTabDisplayed(string $type) {
    if (!$this->findContentTab($type)) {
      throw new \Exception("The tab '$type' was not found in the page but it should be displayed.");
    }
  }

  /**
   * Provides a helper that checks if content tab exist and returns it.
   *
   * @param string $type
   *   The text of the content tab.
   *
   * @return \Behat\Mink\Element\NodeElement|null
   *   The link as node element or NULL.
   */
  protected function findContentTab(string $type): ?NodeElement {
    $xpath = "//div[contains(concat(' ', normalize-space(@class), ' '), ' tab__container ')]//span[@class='tab__text--lower'][normalize-space(string(.)) = '$type']" .
      "/ancestor-or-self::a[@class and contains(concat(' ', normalize-space(@class), ' '), ' tab--content-type ')]";
    return $this->getSession()->getPage()->find('xpath', $xpath);
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
  public function assertContentTypeFacetSelected(string $type): void {
    $xpath = "//a[contains(concat(' ', normalize-space(@class), ' '), ' tab--content-type ') and contains(concat(' ', normalize-space(@class), ' '), ' is-active ')]//span[contains(concat(' ', normalize-space(@class), ' '), ' tab__text--lower ')][normalize-space(string(.)) = '$type']";
    $element = $this->getSession()->getPage()->find('xpath', $xpath);
    Assert::assertNotEmpty($element);
  }

  /**
   * Checks that the given content facet checkbox item is selected.
   *
   * @param string $bundle
   *   The text of the checkbox that should be selected.
   *
   * @throws \Exception
   *   Thrown when the facet checkbox is not selected or not present in the
   *   page.
   *
   * @Then the :bundle content checkbox item should be selected
   */
  public function assertContentFacetCheckboxSelected(string $bundle): void {
    if ($this->browserSupportsJavascript()) {
      // The following xpath searches for
      // * Any div
      // ** that has a class named facets-widget-checkbox
      // ** and contains a li element
      // *** that contains an input element
      // **** that has an attribute named 'type' with value 'checkbox'
      // **** and has an attribute named 'checked' with value 'checked'
      // *** and contains a span element
      // **** with the text has the value $bundle.
      $xpath = "//div[contains(concat(' ', normalize-space(@class), ' '), ' facets-widget-checkbox ')][.//li[.//input[@type='checkbox'][@checked='checked']][.//span[text()='$bundle']]]";
    }
    else {
      // The following xpath searches for
      // * Any div
      // ** that has a class named facets-widget-checkbox
      // ** and contains an a element
      // *** that has a class named 'is-active'
      // *** and contains a span element
      // **** that has a class named 'facet-item__value'
      // **** and the text has the value $bundle.
      $xpath = "//div[contains(concat(' ', normalize-space(@class), ' '), ' facets-widget-checkbox ')][.//a[contains(concat(' ', normalize-space(@class), ' '), ' is-active ')][.//span[text()='$bundle']]]";
    }
    $element = $this->getSession()->getPage()->find('xpath', $xpath);
    Assert::assertNotEmpty($element);
  }

  /**
   * Asserts that certain content type facet items are shown on the page.
   *
   * @param string $labels
   *   A comma-separated list of facet item labels.
   *
   * @throws \Exception
   *   Thrown when a wanted facet item is not shown in the page.
   *
   * @When I should see the following facet items :labels in this order
   */
  public function assertContentTypeFacetItemsPresent(string $labels): void {
    $labels = $this->explodeCommaSeparatedStepArgument($labels);
    $xpath = "//a[contains(concat(' ', normalize-space(@class), ' '), ' tab--content-type ')]//span[contains(concat(' ', normalize-space(@class), ' '), ' tab__text--lower ')]";
    $elements = $this->getSession()->getPage()->findAll('xpath', $xpath);
    $present = [];

    /** @var \Behat\Mink\Element\NodeElement $element */
    foreach ($elements as $element) {
      $present[] = $element->getText();
    }

    $present = array_map('trim', $present);
    Assert::assertEquals($labels, $present);
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
   * @When I should not see the following facet items :labels
   */
  public function assertContentTypeFacetItemsNotPresent(string $labels): void {
    $labels = $this->explodeCommaSeparatedStepArgument($labels);

    $xpath = "//a[contains(concat(' ', normalize-space(@class), ' '), ' tab--content-type ')]//span[contains(concat(' ', normalize-space(@class), ' '), ' tab__text--lower ')]";
    $elements = $this->getSession()->getPage()->findAll('xpath', $xpath);
    $present = [];

    /** @var \Behat\Mink\Element\NodeElement $element */
    foreach ($elements as $element) {
      $present[] = $element->getText();
    }

    $present = array_map('trim', $present);
    $found = array_intersect($labels, $present);

    if ($found) {
      throw new \Exception('Facet item(s) found, but should not: ' . implode(', ', $found));
    }
  }

  /**
   * Asserts that an inline facet widget exists in the page.
   *
   * @param string $facet
   *   The facet identifier.
   *
   * @Then I should see the :facet inline facet
   */
  public function assertInlineFacetExists(string $facet): void {
    $facet_id = self::getFacetIdFromAlias($facet);
    if (!$this->getSession()->getPage()->find('xpath', "//*[@data-drupal-facet-id='{$facet_id}']")) {
      throw new \Exception("Inline facet '{$facet}' should be found in the page but was not.");
    }
  }

  /**
   * Asserts that an inline facet widget does not exist in the page.
   *
   * @param string $facet
   *   The facet identifier.
   *
   * @Then I should not see the :facet inline facet
   */
  public function assertInlineFacetNotExists(string $facet): void {
    $facet_id = self::getFacetIdFromAlias($facet);
    if ($this->getSession()->getPage()->find('xpath', "//*[@data-drupal-facet-id='{$facet_id}']")) {
      throw new \Exception("Inline facet '{$facet}' should not exist but was found.");
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
   * @throws \Exception
   *   Thrown when the facet or the link inside the facet is not found.
   *
   * @When I click :link in the :facet inline facet
   */
  public function iClickAnInlineFacetItemLink(string $link, string $facet): void {
    $this->findFacetByAlias($facet)->clickLink($link);
  }

  /**
   * Clicks a facet item in an inline facet.
   *
   * @param string $select
   *   The option to select.
   * @param string $facet
   *   The facet alias.
   *
   * @throws \Exception
   *   Thrown when the facet or the link inside the facet is not found.
   *
   * @When I select :option from the :facet select facet
   */
  public function iSelectAnOptionFromFacet(string $select, string $facet): void {
    $facet = $this->findFacetByAlias($facet, NULL, 'select');
    $facet->selectOption($select);
  }

  /**
   * Clicks a facet item in an inline facet form.
   *
   * @param string $select
   *   The option to select.
   * @param string $facet
   *   The facet alias.
   *
   * @throws \Exception
   *   Thrown when the facet or the link inside the facet is not found.
   *
   * @When I select :option from the :facet select facet form
   */
  public function iSelectAnOptionFromFacetForm(string $select, string $facet): void {
    $facet = $this->findFacetByAlias($facet, NULL, 'select', TRUE);
    $facet->selectOption($select);
  }

  /**
   * Clicks in more facet items in an inline facet form.
   *
   * @param string $select
   *   The option to select.
   * @param string $facet
   *   The facet alias.
   *
   * @throws \Exception
   *   Thrown when the facet or the link inside the facet is not found.
   *
   * @When I select :option option in the :facet select facet form
   */
  public function iSelectOtherOptionFromFacetForm(string $select, string $facet): void {
    $facet = $this->findFacetByAlias($facet, NULL, 'select', TRUE);
    $facet->selectOption($select, TRUE);
  }

  /**
   * Asserts a selected option in the .
   *
   * @param string $option
   *   Text value of the option to find.
   * @param string $select
   *   CSS selector of the select field.
   *
   * @throws \Exception
   *
   * @Then the option with text :option from select facet :select is selected
   */
  public function assertSelectFacetOptionSelected(string $option, string $select): void {
    // What appears as a select list in the frontend is actually output as a
    // list of links by the Facets module which is ultimately converted into a
    // select list using JavaScript.
    // @see https://www.drupal.org/project/facets/issues/2937191
    $html_tag = $this->browserSupportsJavaScript() ? 'select' : 'ul';
    $element = $this->findFacetByAlias($select, NULL, $html_tag);
    if (!$element) {
      throw new \Exception(sprintf('The select "%s" was not found in the page %s', $select, $this->getSession()->getCurrentUrl()));
    }
    if ($this->browserSupportsJavaScript()) {
      $this->assertSelectedOption($element, $option);
    }
    else {
      $selected_option = $element->find('css', 'a.is-active');
      if ($selected_option instanceof NodeElement) {
        $text = $selected_option->getText();
        // Selected facet options are prefixed with '(-) '. Strip this.
        $text = preg_replace('/^\(-\) /', '', $text);
        // Ignore duplicate whitespace.
        $option = preg_replace('/\s{2,}/', ' ', $option);
        Assert::assertEquals($option, $text, sprintf('The option "%s" is selected in the "%s" facet, but the option "%s" was expected.', $text, $select, $option));
      }
    }
  }

  /**
   * Asserts a selected option in the select facets form.
   *
   * @param string $option
   *   Text value of the option to find.
   * @param string $select
   *   Title of the select field.
   *
   * @throws \Exception
   *    Throws an exception when the select is not found in page.
   *
   * @Then the option with text :option from select facet form :select is selected
   */
  public function assertSelectFacetFormOptionSelected(string $option, string $select): void {
    $element = $this->findFacetByAlias($select, NULL, 'select', TRUE);
    if (!$element) {
      throw new \Exception(sprintf('The select "%s" was not found in the page %s', $select, $this->getSession()->getCurrentUrl()));
    }
    if ($this->browserSupportsJavaScript()) {
      $this->assertSelectedOption($element, $option);
    }
    else {
      $selected_option = $element->find('css', 'a.is-active');
      if ($selected_option instanceof NodeElement) {
        $text = $selected_option->getText();
        // Selected facet options are prefixed with '(-) '. Strip this.
        $text = preg_replace('/^\(-\) /', '', $text);
        // Ignore duplicate whitespace.
        $option = preg_replace('/\s{2,}/', ' ', $option);
        Assert::assertSame($option, $text, sprintf('The option "%s" is selected in the "%s" facet, but the option "%s" was expected.', $text, $select, $option));
      }
    }
  }

  /**
   * Asserts the list of available options in a select facet.
   *
   * @param string $select
   *   The name of the field element.
   * @param \Behat\Gherkin\Node\TableNode $table
   *   The available list of options.
   *
   * @throws \Exception
   *    Throws an exception when the select is not found or options are not
   *    identical.
   */
  public function assertSelectFacetOptionsAsList($select, TableNode $table) {
    $element = $this->findFacetByAlias($select);
    $this->assertSelectAvailableOptions($element, $table);
  }

  /**
   * Asserts the list of available options in a facet select box.
   *
   * @param string $select
   *   The name of the field element.
   * @param \Behat\Gherkin\Node\TableNode $table
   *   The available list of options.
   *
   * @throws \Exception
   *    Throws an exception when the select is not found or options are not
   *    identical.
   *
   * @Then the :select select facet should contain the following options:
   */
  public function assertSelectOptionsAsList($select, TableNode $table) {
    $element = $this->findFacetByAlias($select, NULL, 'select');
    $this->assertSelectAvailableOptions($element, $table);
  }

  /**
   * Asserts the list of available options in a facet form select box.
   *
   * @param string $select
   *   The name of the field element.
   * @param \Behat\Gherkin\Node\TableNode $table
   *   The available list of options.
   *
   * @throws \Exception
   *    Throws an exception when the select is not found or options are not
   *    identical.
   *
   * @Then the :select select facet form should contain the following options:
   */
  public function assertSelectFacetFormOptionsAsList($select, TableNode $table) {
    $element = $this->findFacetByAlias($select, NULL, 'select', TRUE);
    $this->assertSelectAvailableOptions($element, $table);
  }

  /**
   * Checks a checkbox link in a facet.
   *
   * @param string $option
   *   The label of the checkbox.
   * @param string $facet_type
   *   The label of the facet.
   *
   * @throws \Exception
   *   Thrown when the checkbox is not found.
   *
   * @Given I check the :option checkbox from the :facet_type facet
   */
  public function checkCheckboxFacet(string $option, string $facet_type): void {
    $facet = $this->findFacetByAlias($facet_type);
    /** @var \Behat\Mink\Element\NodeElement[] $node_elements */
    $node_elements = $facet->findAll('xpath', '//li[@class="facet-item"]');
    foreach ($node_elements as $node_element) {
      if ($node_element->getText() === $option) {
        $node_element->click();
        return;
      }
    }

    throw new \Exception("The option '{$option}' was not found in the '{$facet_type}' facet.");
  }

  /**
   * Checks a checkbox in a facet form.
   *
   * @param string $option
   *   The label of the checkbox.
   * @param string $facet_type
   *   The label of the facet.
   *
   * @throws \Exception
   *   Thrown when the checkbox is not found.
   *
   * @Given I check the :option checkbox from the :facet_type facet form
   */
  public function checkCheckboxFacetForm(string $option, string $facet_type): void {
    $facet = $this->findFacetByAlias($facet_type, NULL, '*', TRUE);
    /** @var \Behat\Mink\Element\NodeElement[] $node_elements */
    $node_elements = $facet->findAll('xpath', '//option');
    foreach ($node_elements as $node_element) {
      if ($node_element->getText() === $option) {
        $node_element->click();
        return;
      }
    }

    throw new \Exception("The option '{$option}' was not found in the '{$facet_type}' facet.");
  }

  /**
   * Facets form action buttons.
   *
   * @param string $name
   *   The label of the input.
   *
   * @Given I click :name in facets form
   */
  public function iClickActionsInFacetsForm(string $name) {
    $region = $this->getSession()->getPage();
    $xpath = '//div[contains(concat(" ", normalize-space(@class), " "), " block-facets-form ")]//div[@data-drupal-selector="edit-actions"]';
    $actions = $region->find('xpath', $xpath);

    $element = $actions->find('css', "input[value|='{$name}']");
    $element->submit();
  }

  /**
   * Asserts the autocomplete items from search.
   *
   * This function asserts also the order of the items.
   *
   * @param string $keywords
   *   A list of words to search.
   * @param \Behat\Gherkin\Node\TableNode $table
   *   A list of items to be present.
   *
   * @throws \Exception
   *    Thrown when the suggestions are not present in the page.
   *
   * @Then I enter :keywords in the search and I should see the suggestions:
   */
  public function iShouldSeeTheSuggestions(string $keywords, TableNode $table): void {
    $session = $this->getSession();
    $element = $this->getSearchBarElement();
    $element->setValue($keywords);

    $session->getDriver()->keyDown($element->getXpath(), '', NULL);
    $session->wait(2000);
    $allResults = $session->getPage()->findAll('css', 'ul.search-api-autocomplete-search li');
    $found = array_map(function ($item) {
      /** @var \Behat\Mink\Element\NodeElement $item */
      return $item->getText();
    }, $allResults);

    Assert::assertEquals($table->getColumn(0), $found, "The autocomplete values mismatch the expected ones.");
  }

  /**
   * Asserts the autocomplete single item from search.
   *
   * This function asserts also the order of the items.
   *
   * @param string $keywords
   *   A list of words to search.
   * @param string $suggestion
   *   The word we need to see in suggestion.
   *
   * @throws \Exception
   *   Thrown when the suggestion is not present in the page.
   *
   * @Then I enter :keywords in the search and it should see the suggestion :suggestion
   */
  public function iShouldSeeTheSuggestion(string $keywords, string $suggestion): void {
    $session = $this->getSession();
    $element = $this->getSearchBarElement();
    $element->setValue($keywords);

    $session->getDriver()->keyDown($element->getXpath(), '', NULL);
    $session->wait(500);
    $allResults = $session->getPage()->findAll('css', '.ui-autocomplete a');

    $found = array_map(function ($item) {
      /** @var \Behat\Mink\Element\NodeElement $item */
      return $item->getText();
    }, $allResults);

    Assert::assertEquals([$suggestion], $found, "The autocomplete values mismatch the expected ones.");
  }

  /**
   * Opens the dropdown for the given facet element.
   *
   * @param \Behat\Mink\Element\NodeElement $facet
   *   The facet element, as returned by `TraversingTrait::findFacetByAlias()`.
   */
  protected function openFacetDropdown(NodeElement $facet): void {
    // Only needed when running in a JS enabled browser.
    if ($this->browserSupportsJavaScript()) {
      // Only open the dropdown if it is not already open.
      if (!$this->isFacetDropdownVisible($facet)) {
        // Click the arrow down button and wait for the animation to finish.
        $facet->find('css', '.filter__icon.icon--arrow-down')->click();
        $this->waitUntil(function () use ($facet): bool {
          $is_visible = (bool) $facet->find('css', 'div.mdl-menu__container.is-visible');
          $is_animating = (bool) $facet->find('css', 'ul.filter__menu.is-animating');
          return $is_visible && !$is_animating;
        });
      }
    }
  }

  /**
   * Checks whether the dropdown for the given facet element is visible.
   *
   * @param \Behat\Mink\Element\NodeElement $facet
   *   The facet element, as returned by `TraversingTrait::findFacetByAlias()`.
   *
   * @return bool
   *   TRUE if the dropdown is visible.
   */
  protected function isFacetDropdownVisible(NodeElement $facet): bool {
    // There is no point calling this method when JS is not enabled, since in
    // this case it will always return FALSE.
    $this->assertJavaScriptEnabledBrowser();
    return (bool) $facet->find('css', 'div.mdl-menu__container.is-visible');
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
  public function assertInlineFacetActiveText(string $text, string $facet): void {
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
   * @param \Behat\Gherkin\Node\TableNode $values
   *   A list of items to be present.
   *
   * @throws \Exception
   *   Thrown when the facet is not found in the page.
   *
   * @Then the :facet inline facet should allow selecting the following values:
   */
  public function assertInlineFacetInactiveItems(string $facet, TableNode $values): void {
    $element = $this->findFacetByAlias($facet);
    $found = array_map(function ($item) {
      /** @var \Behat\Mink\Element\NodeElement $item */
      return $item->getText();
    }, $element->findAll('css', 'li.facet-item'));

    Assert::assertEquals($values->getColumn(0), $found, "The '{$facet}' values mismatch the expected ones.");
  }

  /**
   * Asserts the items in a checkbox widget facet.
   *
   * This function asserts also the order of the items.
   *
   * @param string $facet
   *   The facet alias.
   * @param string $values
   *   A comma-separated list of items to be present.
   *
   * @throws \Exception
   *   Thrown when the facet is not found in the page.
   *
   * @Then the :facet checkbox facet should allow selecting the following values :values
   */
  public function assertCheckboxFacetItems(string $facet, string $values): void {
    $element = $this->findFacetByAlias($facet);
    $found = array_map(function (NodeElement $item) {
      $text = $item->getText();
      // If a value is selected, it is prefixed with a '(-) ' in the text. Clean
      // that out so that it is more readable.
      return preg_replace('/^\(-\) /', '', $text);
    }, $element->findAll('css', 'li.facet-item'));

    $values = $this->explodeCommaSeparatedStepArgument($values);
    Assert::assertEquals($values, $found, "The '{$facet}' values mismatch the expected ones.");
  }

  /**
   * Enters keywords in the search bar.
   *
   * Note that this will just enter the keywords, it will not launch the search.
   *
   * @param string $keywords
   *   A list of words to search.
   *
   * @throws \Exception
   *   Thrown when the header search bar is not found.
   *
   * @When I enter :keywords in the search bar
   */
  public function enterSearchKeywords(string $keywords): void {
    $element = $this->getSearchBarElement();
    $element->setValue($keywords);
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
   * @When I enter :keywords in the search bar and press enter
   */
  public function launchSearchFromHeader(string $keywords): void {
    if ($this->browserSupportsJavaScript()) {
      $element = $this->getSearchBarElement();
      $element->setValue($keywords);
      $this->submitSearch();
    }
    else {
      // The header search form doesn't have a submit button but is submitted by
      // pressing the Enter key. Sending keys is not supported by GoutteDriver,
      // so we cannot fake the pressing of the Enter key. We will simply
      // redirect to the search page.
      $this->getSession()->visit($this->locatePath('/search?keys=' . $keywords));
    }
  }

  /**
   * Press a key in the search field.
   *
   * Works only in Javascript-enabled browsers.
   *
   * Don't use this for submitting the form by sending an `enter` key since this
   * does not trigger the form submission. Use ::submitSearch() instead.
   *
   * @param string $key
   *   The human readable name of the key to press.
   *
   * @throws \Exception
   *   Thrown when the browser doesn't support Javascript or when the search
   *   field is not found.
   *
   * @When I press :key in the search bar
   */
  public function pressKeyInSearchBar(string $key): void {
    $this->assertJavaScriptEnabledBrowser();
    $element = $this->getSearchBarElement();
    $this->pressKeyInElement($key, $element);
  }

  /**
   * Submits the search form.
   *
   * For some reason sending a return key press to the form input field does not
   * result in a form submission in Selenium, even though it works when
   * performed manually in the browser. This step forces a form submission by
   * calling the Selenium method directly.
   *
   * @throws \Exception
   *   Thrown when the browser doesn't support Javascript or when the search
   *   field is not found.
   *
   * @When I submit the search by pressing enter
   */
  public function submitSearch(): void {
    $this->assertJavaScriptEnabledBrowser();
    $element = $this->getSearchBarElement();
    $element->submit();
  }

  /**
   * Asserts that a link has the "rel" attribute set to "nofollow".
   *
   * @param string $link
   *   The link title.
   *
   * @throws \Exception
   *   Thrown when the link is not found or when the link doesn't have the "rel"
   *   attribute set to "nofollow".
   *
   * @Then search engines should be discouraged to follow the link :link
   */
  public function assertLinkHasRelNofollow(string $link): void {
    $element = $this->getSession()->getPage()->findLink($link);

    if (!$element) {
      throw new \Exception("Link '$link' not found in the page.");
    }

    if ($element->getAttribute('rel') !== 'nofollow') {
      throw new \Exception("Link '$link' doesn't have the 'rel' attribute set to 'nofollow'.");
    }
  }

  /**
   * Opens the search bar.
   *
   * @When I open the search bar by clicking on the search icon
   */
  public function openSearchBar() {
    // One can only open the search bar if JavaScript is enabled.
    $this->assertJavaScriptEnabledBrowser();
    $element = $this->getSearchBarElement();
    $element->focus();
  }

  /**
   * Returns the search bar as a Mink page element.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The search bar.
   */
  protected function getSearchBarElement(): NodeElement {
    $elements = $this->getElementsMatchingElementAlias('search bar');

    Assert::assertNotEmpty($elements, 'Could not find the search field in the page.');
    Assert::assertCount(1, $elements, 'There are multiple search fields on the page.');

    return reset($elements);
  }

  /**
   * Asserts that certain facet summary items are shown on the page.
   *
   * @param string $labels
   *   A comma-separated list of facet item labels.
   *
   * @throws \Exception
   *   Thrown when a wanted facet item is not shown in the page.
   *
   * @When I should see the following facet summary :labels
   */
  public function assertFacetSummary(string $labels): void {
    $labels = $this->explodeCommaSeparatedStepArgument($labels);
    $xpath = "//div[contains(concat(' ', normalize-space(@class), ' '), ' block-facets-summary-blocksearch-facets-summary ')]//li[contains(concat(' ', normalize-space(@class), ' '), ' facet-summary-item--facet ')]";
    $elements = $this->getSession()->getPage()->findAll('xpath', $xpath);
    $present = $present_labels = [];

    /** @var \Behat\Mink\Element\NodeElement $element */
    foreach ($elements as $element) {
      $present[] = $element->getText();
    }

    // Add label close in front of label.
    foreach ($labels as $label) {
      $present_labels[] = $label . ' close';
    }

    $present = array_map('trim', $present);
    $present_labels = array_map('trim', $present_labels);
    Assert::assertEquals($present_labels, $present);
  }

  /**
   * Remove facet summary item that are on the page.
   *
   * @param string $label
   *   A facet summary item label.
   *
   * @throws \Exception
   *   Thrown when a wanted facet item is not shown in the page.
   *
   * @When I should remove the following facet summary :label
   */
  public function removeFacetSummary(string $label): void {

    $xpath = '//div[contains(concat(" ", normalize-space(@class), " "), " block-facets-summary-blocksearch-facets-summary ")]//span[text()="' . $label . '"]';
    $element = $this->getSession()->getPage()->find('xpath', $xpath);

    if (empty($element)) {
      throw new \Exception("The $label facet summary item was not found in the page.");
    }

    $element->click();
  }

  /**
   * Asserts that the expected number of summaries are shown.
   *
   * @param string|null $number
   *   The expected number of summaries. This is a string rather than an integer
   *   because step definitions are represented in text.
   *
   * @throws \Exception
   *    Thrown when the region is not found.
   *
   * @Then the page should contain :number facet(s) summary/summaries
   * @Then the page should not contain any facet summary
   */
  public function assertFacetsSummaryCount(?string $number = NULL): void {
    $number = $number === 'one' ? 1 : (int) $number;
    $xpath = "//div[contains(concat(' ', normalize-space(@class), ' '), ' block-facets-summary-blocksearch-facets-summary ')]//li[contains(concat(' ', normalize-space(@class), ' '), ' facet-summary-item--facet ')]";
    $elements = $this->getSession()->getPage()->findAll('xpath', $xpath);
    Assert::assertCount($number, $elements);
  }

}
