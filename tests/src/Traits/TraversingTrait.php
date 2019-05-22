<?php

namespace Drupal\joinup\Traits;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Element\TraversableElement;
use Behat\Mink\Exception\ElementNotFoundException;

/**
 * Helper methods to deal with traversing of page elements.
 */
trait TraversingTrait {

  /**
   * Retrieves a select field by label.
   *
   * @param string $select
   *   The name of the select element.
   * @param \Behat\Mink\Element\TraversableElement $region
   *   (optional) The region in which to search for the select. Defaults to the
   *   whole page.
   *
   * @return \Behat\Mink\Element\TraversableElement
   *   The select element.
   *
   * @throws \Exception
   *   Thrown when no select field is found.
   */
  protected function findSelect(string $select, TraversableElement $region = NULL): TraversableElement {
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
   * Retrieves the options of a select field.
   *
   * @param \Behat\Mink\Element\NodeElement $select
   *   The select element.
   *
   * @return array
   *   The options text keyed by option value.
   */
  protected function getSelectOptions(NodeElement $select) {
    $options = [];
    foreach ($select->findAll('xpath', '//option') as $element) {
      /** @var \Behat\Mink\Element\NodeElement $element */
      $options[$element->getValue()] = trim($element->getText());
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
  protected function getSelectOptgroups(NodeElement $select) {
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
  protected function findVerticalTab($tab) {
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
  protected function getRegion($region) {
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
  protected function getTiles($region = NULL) {
    /** @var \Behat\Mink\Element\DocumentElement $regionObj */
    if ($region === NULL) {
      $regionObj = $this->getSession()->getPage();
    }
    else {
      $regionObj = $this->getRegion($region);
    }

    $result = [];
    foreach ($regionObj->findAll('css', '.listing__item--tile') as $element) {
      $title_element = $element->find('css', ' .listing__title');
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
    return $this->getListingByHeading('listing__item--tile', $heading);
  }

  /**
   * Finds a card element by its heading.
   *
   * @param string $heading
   *   The heading of the card to find.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The element found.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   Thrown when the element is not found.
   */
  protected function getCollectionSubscriptionCardByHeading(string $heading): NodeElement {
    return $this->getListingByHeading('collection-subscription', $heading);
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
    // Locate all the items.
    $xpath = '//*[@class and contains(concat(" ", normalize-space(@class), " "), " ' . $type . ' ")]';
    // That have a heading with the specified text.
    $xpath .= '[.//*[@class and contains(concat(" ", normalize-space(@class), " "), " listing__title ")][normalize-space()="' . $heading . '"]]';

    $item = $this->getSession()->getPage()->find('xpath', $xpath);

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
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The facet node element.
   *
   * @throws \Exception
   *   Thrown when the facet is not found in the page.
   */
  protected function findFacetByAlias($alias) {
    $facet_id = self::getFacetIdFromAlias($alias);
    $element = $this->getSession()->getPage()->find('xpath', "//*[@data-drupal-facet-id='{$facet_id}']");

    if (!$element) {
      throw new \Exception("The facet '$alias' was not found in the page.");
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
  protected static function getFacetIdFromAlias($alias) {
    $mappings = [
      'collection type' => 'collection_type',
      'collection policy domain' => 'collection_policy_domain',
      'from' => 'group',
      'policy domain' => 'policy_domain',
      'solution policy domain' => 'solution_policy_domain',
      'solution spatial coverage' => 'solution_spatial_coverage',
      'spatial coverage' => 'spatial_coverage',
      'My solutions content' => 'solution_my_content',
      'My collections content' => 'collection_my_content',
      'My content' => 'content_my_content',
      'Event date' => 'event_date',
      'Collection event date' => 'collection_event_type',
    ];

    if (!isset($mappings[$alias])) {
      throw new \Exception("No facet id mapping found for '$alias'.");
    }

    return $mappings[$alias];
  }

  /**
   * Gets the date or time component of a date sub-field in a date range field.
   *
   * @param string $field
   *   The date range field name.
   * @param string $date
   *   The sub-field name. Either "start" or "end".
   * @param string $component
   *   The sub-field component. Either "date" or "time".
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The date or time component element.
   *
   * @throws \Exception
   *   Thrown when the date range field is not found.
   */
  protected function findDateRangeComponent($field, $date, $component) {
    /** @var \Behat\Mink\Element\NodeElement $fieldset */
    $fieldset = $this->getSession()->getPage()->find('named', ['fieldset', $field]);

    if (!$fieldset) {
      throw new \Exception("The '$field' field was not found.");
    }

    $date = ucfirst($date) . ' date';
    /** @var \Behat\Mink\Element\NodeElement $element */
    $element = $fieldset->find('xpath', '//h4[text()="' . $date . '"]//following-sibling::div[1]');

    if (!$element) {
      throw new \Exception("The '$date' sub-field of the '$field' field was not found.");
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
  protected function findDisabledField($label) {
    $page = $this->getSession()->getPage();
    // The *[self::div|self::fieldset] is because ief sets the class 'form-item'
    // in a fieldset rather than a div.
    $element = $page->find('xpath', "//*[self::div|self::fieldset][contains(normalize-space(@class), 'form-item') and contains(., '{$label}')]//input[@disabled and @disabled='disabled']");
    if (empty($element)) {
      // Try again to fetch fields with textareas. These are marked as disabled
      // by setting the class 'form-disabled' to the wrapper div and not in the
      // input.
      $element = $page->find('xpath', "//div[contains(normalize-space(@class), 'form-disabled') and contains(., '{$label}')]");
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
  protected function findLinksMarkedAsActive($region = NULL) {
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
   * @param string $region
   *   The region in which the element should be found.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The element.
   *
   * @throws \Exception
   *   Thrown when the element is not found in the given region.
   *
   * @see \Behat\Mink\Selector\NamedSelector
   */
  protected function findNamedElementInRegion($locator, $element, $region) {
    $session = $this->getSession();
    $region_object = $session->getPage()->find('region', $region);
    if (!$region_object) {
      throw new \Exception(sprintf('No region "%s" found on the page %s.', $region, $session->getCurrentUrl()));
    }

    // Find the named element in the region.
    $element = $region_object->find('named', [$element, $locator]);
    if (!$element) {
      throw new \Exception(sprintf('No element with locator "%s" found in the "%s" region on the page %s.', $locator, $region, $session->getCurrentUrl()));
    }
    return $element;
  }

  /**
   * Finds an image element in a region given the file name.
   *
   * @param string $filename
   *   The file name.
   * @param \Behat\Mink\Element\NodeElement $region
   *   (optional) The region to check in.
   *
   * @return bool
   *   Whether the element exists or not in the given region.
   */
  protected function findImageInRegion($filename, NodeElement $region = NULL) {
    if (empty($region)) {
      $region = $this->getSession()->getPage();
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
    $elements = $region->findAll('xpath', "//img");
    foreach ($elements as $element) {
      $html = $element->getOuterHtml();
      if (preg_match($expression, $html)) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
