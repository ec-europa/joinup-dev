<?php

declare(strict_types = 1);

namespace Drupal\joinup\Traits;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Element\TraversableElement;
use Behat\Mink\Exception\ElementNotFoundException;
use PHPUnit\Framework\Assert;

/**
 * Helper methods to deal with traversing of page elements.
 */
trait TraversingTrait {

  /**
   * Searches for any kind of field in a form by label.
   *
   * @param string $field
   *   The field label.
   * @param \Behat\Mink\Element\TraversableElement|null $region
   *   (Optional) The region to search in. If a region is not provided, the
   *   whole page will be used.
   *
   * @return \Behat\Mink\Element\TraversableElement|null
   *   The field element or NULL if not found.
   */
  protected function findAnyFormField(string $field, ?TraversableElement $region = NULL): ?TraversableElement {
    if (!$region) {
      $region = $this->getSession()->getPage();
    }

    $element = NULL;
    if (!$element = $region->findField($field)) {
      // Complex fields in Drupal might not be directly linked to actual field
      // elements such as 'select' and 'input', so try both the standard
      // findField() as well as an XPath expression that finds the given label
      // inside any element marked as a form item.
      $xpath = '//*[contains(concat(" ", normalize-space(@class), " "), " form-item ") and .//label[text() = "' . $field . '"]]';
      $element = $region->find('xpath', $xpath);
    }

    return $element;
  }

  /**
   * Retrieves a select field by label.
   *
   * @param string $select
   *   The name of the select element.
   * @param \Behat\Mink\Element\TraversableElement|null $region
   *   (optional) The region in which to search for the select. Defaults to the
   *   whole page.
   *
   * @return \Behat\Mink\Element\TraversableElement
   *   The select element.
   *
   * @throws \Exception
   *   Thrown when no select field is found.
   */
  protected function findSelect(string $select, ?TraversableElement $region = NULL): TraversableElement {
    if (empty($region)) {
      $region = $this->getSession()->getPage();
    }
    /** @var \Behat\Mink\Element\NodeElement $element */
    $element = $region->find('named', ['select', $select]);

    if (empty($element)) {
      throw new \Exception("Select field '{$select}' not found.");
    }

    return $element;
  }

  /**
   * Helper method that asserts a selected option of a select element.
   *
   * @param \Behat\Mink\Element\NodeElement $element
   *   The select node element.
   * @param string $expected
   *   The select option.
   *
   * @throws \Exception
   *   Thrown if there is no selected option or the selected option is not the
   *   correct one.
   */
  protected function assertSelectedOption(NodeElement $element, string $expected): void {
    $option_element = $element->find('xpath', '//option[@selected="selected"]');
    if (!$option_element) {
      throw new \Exception('No option is selected in the requested select');
    }
    $actual = $option_element->getText();

    // Ignore duplicated whitespace.
    $actual = preg_replace("/\s{2,}/", " ", $actual);
    $expected = preg_replace("/\s{2,}/", " ", $expected);

    if (trim($actual) !== $expected) {
      throw new \Exception(sprintf('The option "%s" was not selected in the page %s, %s was selected', $expected, $this->getSession()->getCurrentUrl(), $option_element->getHtml()));
    }
  }

  /**
   * Helper method that asserts the available options of select fields.
   *
   * @param \Behat\Mink\Element\NodeElement $element
   *   The select element.
   * @param \Behat\Gherkin\Node\TableNode $table
   *   The available list of options.
   *
   * @throws \Exception
   *    Throws an exception when the select is not found or options are not
   *    identical.
   */
  protected function assertSelectAvailableOptions(NodeElement $element, TableNode $table): void {
    $available_options = $this->getSelectOptions($element);
    $rows = $table->getColumn(0);

    // Ignore duplicated whitespace.
    $strip_multiple_spaces = function (string $option): string {
      $option = preg_replace("/\s{2,}/", " ", $option);
      return $option;
    };
    $available_options = array_map($strip_multiple_spaces, $available_options);
    $rows = array_map($strip_multiple_spaces, $rows);

    Assert::assertEquals($rows, $available_options);
  }

  /**
   * Retrieves the options of a select field.
   *
   * @param \Behat\Mink\Element\NodeElement $select
   *   The select element.
   *
   * @return array
   *   The options text keyed by option value.
   */
  protected function getSelectOptions(NodeElement $select): array {
    $options = [];
    foreach ($select->findAll('xpath', '//option') as $element) {
      /** @var \Behat\Mink\Element\NodeElement $element */
      $options[] = trim($element->getText());
    }

    return $options;
  }

  /**
   * Retrieves the optgroups of a select field.
   *
   * @param \Behat\Mink\Element\NodeElement $select
   *   The select element.
   *
   * @return array
   *   The optgroups labels.
   */
  protected function getSelectOptgroups(NodeElement $select): array {
    $optgroups = [];
    foreach ($select->findAll('xpath', '//optgroup') as $element) {
      /** @var \Behat\Mink\Element\NodeElement $element */
      $optgroups[] = trim($element->getAttribute('label'));
    }

    return $optgroups;
  }

  /**
   * Finds a vertical tab by its title.
   *
   * @param string $tab
   *   The title of the vertical tab.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The vertical tab element.
   *
   * @throws \Exception
   *   Thrown when no tab element is found.
   */
  protected function findVerticalTab(string $tab): NodeElement {
    // Xpath to find the vertical tabs.
    $xpath = "//li[@class and contains(concat(' ', normalize-space(@class), ' '), ' vertical-tabs__menu-item ')]";
    // Filter down to the tab containing a link with the provided text.
    $xpath .= "[.//a[./@href]/strong[@class and contains(concat(' ', normalize-space(@class), ' '), ' vertical-tabs__menu-item-title ')]"
      . "[normalize-space(string(.)) = '$tab']]";
    $element = $this->getSession()->getPage()->find('xpath', $xpath);

    if ($element === NULL) {
      throw new \Exception('Tab not found: ' . $tab);
    }

    return $element;
  }

  /**
   * Retrieves a region container from the page.
   *
   * @param string $region
   *   The region label as defined in the behat.yml.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The region element.
   *
   * @throws \Exception
   *    Thrown when the region is not found.
   */
  protected function getRegion(string $region): NodeElement {
    $session = $this->getSession();
    $regionObj = $session->getPage()->find('region', $region);
    if (!$regionObj) {
      throw new \Exception(sprintf('No region "%s" found on the page %s.', $region, $session->getCurrentUrl()));
    }
    return $regionObj;
  }

  /**
   * Returns the tiles found in the page or a region of it.
   *
   * @param string|null $region
   *   The region label. If no region is provided, the search will be on the
   *    whole page.
   *
   * @return \Behat\Mink\Element\NodeElement[]
   *   An array of tiles elements, keyed by tile title.
   */
  protected function getTiles($region = NULL): array {
    /** @var \Behat\Mink\Element\DocumentElement $regionObj */
    if ($region === NULL) {
      $regionObj = $this->getSession()->getPage();
    }
    else {
      $regionObj = $this->getRegion($region);
    }

    $result = [];
    // @todo The `.listing__item--tile` selector is part of the original Joinup
    //   theme and can be removed once we have fully migrated to the new theme.
    foreach ($regionObj->findAll('css', '.listing__item--tile, article.tile') as $element) {
      // @todo The `.listing__title` selector is part of the original Joinup
      //   theme and can be removed once we migrated to the new theme.
      $title_element = $element->find('css', ' .listing__title, h2 a');
      // Some tiles don't have a title, like the one to create a new collection
      // in the collections page.
      if ($title_element) {
        $title = $title_element->getText();
        $result[$title] = $element;
      }
    }

    return $result;
  }

  /**
   * Finds a tile element by its heading.
   *
   * @param string $heading
   *   The heading of the tile to find.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The element found.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   Thrown when the element is not found.
   */
  protected function getTileByHeading(string $heading): NodeElement {
    // @todo The `.listing__item--tile` selector is part of the original Joinup
    //   theme and can be removed once we have fully migrated to the new theme.
    try {
      return $this->getListingByHeading('listing__item--tile', $heading);
    }
    catch (ElementNotFoundException $e) {
      return $this->getListingByHeading('tile', $heading);
    }
  }

  /**
   * Finds a list item element by its heading.
   *
   * @param string $type
   *   The class of the element that is searched for.
   * @param string $heading
   *   The heading on the item.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The found node element.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   Thrown when the element is not found.
   */
  protected function getListingByHeading(string $type, string $heading): NodeElement {
    // Locate all the items in the old theme.
    // @todo This can be removed once we are fully migrated to the new theme.
    $xpath = '//*[@class and contains(concat(" ", normalize-space(@class), " "), " ' . $type . ' ")]';
    // That have a heading with the specified text.
    $xpath .= '[.//*[@class and contains(concat(" ", normalize-space(@class), " "), " listing__title ")][normalize-space()="' . $heading . '"]]';

    $item = $this->getSession()->getPage()->find('xpath', $xpath);

    if (!$item) {
      // Locate all the items.
      $xpath = '//*[@class and contains(concat(" ", normalize-space(@class), " "), " ' . $type . ' ")]';
      // That have a heading with the specified text.
      $xpath .= '[.//h2/a[normalize-space()="' . $heading . '"]]';

      $item = $this->getSession()->getPage()->find('xpath', $xpath);
    }

    if (!$item) {
      // Throw a specific exception, so it can be catched by steps that need to
      // assert that a tile is not present.
      throw new ElementNotFoundException($this->getSession()->getDriver(), "'$heading' $type item.");
    }

    return $item;
  }

  /**
   * Finds a facet by alias.
   *
   * @param string $alias
   *   The facet alias.
   * @param \Behat\Mink\Element\NodeElement|null $region
   *   (optional) Limit the search to a specific region. If empty, the whole
   *   page will be used. Defaults to NULL.
   * @param string $html_tag
   *   (optional) Limit to a specific html tag when searching for an element.
   *   This can be useful in cases where the data drupal facet id is placed in
   *   more than one html tag e.g. the dropdown has the id placed in both the
   *   <li> tag of links as well as the <select> element.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The facet node element.
   *
   * @throws \Exception
   *   Thrown when the facet is not found in the designated area.
   */
  protected function findFacetByAlias(string $alias, ?NodeElement $region = NULL, string $html_tag = '*'): NodeElement {
    if ($region === NULL) {
      $region = $this->getSession()->getPage();
    }
    $facet_id = self::getFacetIdFromAlias($alias);
    $element = $region->find('xpath', "//{$html_tag}[@data-drupal-facet-id='{$facet_id}']");

    if (!$element) {
      throw new \Exception("The facet '$alias' was not found in the page.");
    }

    return $element;
  }

  /**
   * Finds a facet form by alias.
   *
   * @param string $alias
   *   The facet form alias.
   * @param \Behat\Mink\Element\NodeElement|null $region
   *   (optional) Limit the search to a specific region. If empty, the whole
   *   page will be used. Defaults to NULL.
   * @param string $html_tag
   *   (optional) Limit to a specific html tag when searching for an element.
   *   This can be useful in cases where the data drupal facet id is placed in
   *   more than one html tag e.g. the dropdown has the id placed in both the
   *   <li> tag of links as well as the <select> element.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The facet node element.
   *
   * @throws \Exception
   *   Thrown when the facet form is not found in the designated area.
   */
  protected function findFacetFormByAlias(string $alias, ?NodeElement $region = NULL, string $html_tag = '*'): NodeElement {
    if ($region === NULL) {
      $region = $this->getSession()->getPage();
    }
    $facet_id = self::getFacetFormIdFromAlias($alias);
    $element = $region->find('xpath', "//{$html_tag}[@data-drupal-selector='{$facet_id}']");

    if (!$element) {
      throw new \Exception("The facet form '$alias' was not found in the page.");
    }

    return $element;
  }

  /**
   * Maps an alias to an actual facet id.
   *
   * The facet id is used as "drupal-data-facet-id" property.
   *
   * @param string $alias
   *   The facet alias.
   *
   * @return string
   *   The facet id.
   *
   * @throws \Exception
   *   Thrown when the mapping is not found.
   */
  protected static function getFacetIdFromAlias(string $alias): string {
    $mappings = [
      'collection type' => 'collection_type',
      'collection topic' => 'collection_topic',
      'collection/solution' => 'group',
      'topic' => 'topic',
      'solution topic' => 'solution_topic',
      'solution spatial coverage' => 'solution_spatial_coverage',
      'spatial coverage' => 'spatial_coverage',
      'My solutions content' => 'solution_my_content',
      'My collections content' => 'collection_my_content',
      'My content' => 'content_my_content',
      'Event date' => 'event_date',
      'EIF recommendations' => 'category',
      'Collection event date' => 'collection_event_type',
      'Content types' => 'type',
      'eif principle' => 'principle',
      'eif interoperability layer' => 'interoperability_layer',
      'eif conceptual model' => 'conceptual_model',
    ];

    if (!isset($mappings[$alias])) {
      throw new \Exception("No facet id mapping found for '$alias'.");
    }

    return $mappings[$alias];
  }

  /**
   * Maps an alias to an actual facet form id.
   *
   * The facet id is used as "data-drupal-selector" property.
   *
   * @param string $alias
   *   The facet form alias.
   *
   * @return string
   *   The facet form id.
   *
   * @throws \Exception
   *   Thrown when the mapping is not found.
   */
  protected static function getFacetFormIdFromAlias(string $alias): string {
    $mappings = [
      'collection/solution' => 'edit-group',
      'topic' => 'edit-topic',
      'spatial coverage' => 'edit-spatial-coverage',
      'Content types' => 'edit-type',
    ];

    if (!isset($mappings[$alias])) {
      throw new \Exception("No facet form id mapping found for '$alias'.");
    }

    return $mappings[$alias];
  }

  /**
   * Gets the date or time component of a date sub-field in a date range field.
   *
   * @param string $field
   *   The date range field name.
   * @param string $component
   *   The sub-field component. Either "date" or "time".
   * @param string|null $date
   *   (optional) The sub-field name. Either "start" or "end". If left empty, it
   *   is assumed that the field is a simple datetime field and not a range,
   *   thus, the date or time components are looked in the whole field.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The date or time component element.
   *
   * @throws \Exception
   *   Thrown when the date range field is not found.
   */
  protected function findDateRangeComponent(string $field, string $component, ?string $date = NULL): NodeElement {
    /** @var \Behat\Mink\Element\NodeElement $fieldset */
    $fieldset = $this->getSession()->getPage()->find(
      'named',
      ['fieldset', $field]
    );

    if (!$fieldset) {
      throw new \Exception("The '$field' field was not found.");
    }

    if ($date !== NULL) {
      $date = ucfirst($date) . ' date';
      /** @var \Behat\Mink\Element\NodeElement $element */
      $element = $fieldset->find('xpath', '//h4[text()="' . $date . '"]//following-sibling::div[1]');
      if (!$element) {
        throw new \Exception("The '$date' sub-field of the '$field' field was not found.");
      }
    }
    else {
      $element = $fieldset;
    }

    $component_node = $element->findField(ucfirst($component));
    if (!$component_node) {
      throw new \Exception("The '$component' component for the '$field' '$element' was not found.");
    }

    return $component_node;
  }

  /**
   * Searches for a field that is disabled.
   *
   * @param string $label
   *   The label of the field.
   *
   * @return \Behat\Mink\Element\NodeElement|null
   *   The date or time component element.
   */
  protected function findDisabledField(string $label): ?NodeElement {
    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $this->getSession()->getPage();
    // The *[self::div|self::fieldset] is because ief sets the class 'form-item'
    // in a fieldset rather than a div.
    $element = $page->find('xpath', "//*[self::div|self::fieldset][contains(concat(' ', normalize-space(@class), ' '), ' form-item ') and contains(., '{$label}')]//input[@disabled and @disabled='disabled']");
    if (empty($element)) {
      // Try again to fetch fields with textareas. These are marked as disabled
      // by setting the class 'form-disabled' to the wrapper div and not in the
      // input.
      $element = $page->find('xpath', "//div[contains(concat(' ', normalize-space(@class), ' '), ' form-disabled ') and contains(., '{$label}')]");
    }
    if (!$element) {
      // Try again to find a button.
      $element = $page->findButton($label);
    }
    return $element;
  }

  /**
   * Returns the active links in the page or in a specific region.
   *
   * An "active" link is a link with the class "is-active" or with the class
   * "active-trail", which indicates that it is in the active trail of the
   * current page.
   *
   * @param string|null $region
   *   The region label. If no region is provided, the search will be on the
   *    whole page.
   *
   * @return \Behat\Mink\Element\NodeElement[]|null
   *   An array of node elements matching the search.
   */
  protected function findLinksMarkedAsActive($region = NULL): ?array {
    if ($region === NULL) {
      /** @var \Behat\Mink\Element\DocumentElement $regionObj */
      $regionObj = $this->getSession()->getPage();
    }
    else {
      $regionObj = $this->getRegion($region);
    }

    return $regionObj->findAll('css', 'a.is-active, a.active-trail');
  }

  /**
   * Returns the named element with the given locator, in the given region.
   *
   * Use this to easily locate "named elements" such as buttons, links, fields,
   * checkboxes etc in a given region.
   *
   * For the full list of supported elements, check NamedSelector::$selectors.
   *
   * @param string $locator
   *   The locator that identifies this particular element. This varies by
   *   element type, but it is often a CSS ID, title, text or value that is set
   *   on the element.
   * @param string $element
   *   The element name, e.g. 'fieldset', 'field', 'link', 'button', 'content',
   *   'select', 'checkbox', 'radio', 'file', 'optgroup', 'option', 'table', ...
   * @param \Behat\Mink\Element\TraversableElement|null $region
   *   (optional) The region to check in.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The element.
   *
   * @throws \Exception
   *   Thrown when the element is not found in the given region.
   *
   * @see \Behat\Mink\Selector\NamedSelector
   */
  protected function findNamedElementInRegion(string $locator, string $element, ?TraversableElement $region = NULL): TraversableElement {
    if (empty($region)) {
      $region = $this->getSession()->getPage();
    }
    $session = $this->getSession();
    $element = $region->find('named', [$element, $locator]);
    if (!$element) {
      throw new \Exception(sprintf('No element with locator "%s" found in the "%s" region on the page %s.', $locator, $region, $session->getCurrentUrl()));
    }
    return $element;
  }

  /**
   * Returns selectors used to find elements with a human readable identifier.
   *
   * @param string $alias
   *   A human readable element identifier.
   *
   * @return array[]
   *   An indexed array of selectors intended to be used with Mink's `find()`
   *   methods. Each value is a tuple containing two strings:
   *   - 0: the selector, e.g. 'css' or 'xpath'.
   *   - 1: the locator.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the element name is not defined.
   */
  protected function getSelectorsMatchingElementAlias(string $alias): array {
    $elements = [
      // The various search input fields.
      [
        'names' => [
          'search bar',
          'search bars',
          'search field',
          'search fields',
        ],
        'selectors' => [
          // The site-wide search field in the top right corner.
          ['css', 'input#search-bar__input'],
          // The search field on the search result pages.
          ['css', '#block-exposed-form-search-page input.form-text'],
        ],
      ],
    ];

    foreach ($elements as $element) {
      if (in_array($alias, $element['names'])) {
        return $element['selectors'];
      }
    }

    throw new \InvalidArgumentException("No selectors are defined for the element named '$alias'.");
  }

  /**
   * Returns elements that match the given human readable identifier.
   *
   * @param string $alias
   *   A human readable element identifier.
   *
   * @return \Behat\Mink\Element\NodeElement[]
   *   The elements matching the identifier.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the element name is not defined.
   */
  protected function getElementsMatchingElementAlias(string $alias): array {
    $elements = [];

    foreach ($this->getSelectorsMatchingElementAlias($alias) as $selector_tuple) {
      [$selector, $locator] = $selector_tuple;
      $elements = array_merge($elements, $this->getSession()->getPage()->findAll($selector, $locator));
    }

    return $elements;
  }

  /**
   * Checks if an image with a given file name exists in a given element.
   *
   * @param string $filename
   *   The file name.
   * @param \Behat\Mink\Element\NodeElement|null $element
   *   (optional) The element to check in. If omitted the entire page will be
   *   checked.
   *
   * @return bool
   *   Whether the element exists or not in the given element or page.
   */
  protected function hasImage(string $filename, ?NodeElement $element = NULL): bool {
    if (empty($element)) {
      $element = $this->getSession()->getPage();
    }

    // Drupal appends an underscore and a number to the filename when duplicate
    // files are uploaded, for example when a test is run more than once.
    // The XPath and selector version that we are using does not support regular
    // expressions and we cannot easily search for the file name otherwise.
    // The elements are loaded instead and the regular expression is being run
    // in php.
    $parts = pathinfo($filename);
    $extension = $parts['extension'];
    $filename = $parts['filename'];
    $expression = '/src="[^"]*' . $filename . '(_\d+)?\.' . $extension . '[^"]*"/';
    $image_elements = $element->findAll('xpath', "//img");
    foreach ($image_elements as $image_element) {
      $html = $image_element->getOuterHtml();
      if (preg_match($expression, $html)) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
