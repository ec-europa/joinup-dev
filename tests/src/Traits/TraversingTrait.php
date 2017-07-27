<?php

namespace Drupal\joinup\Traits;

use Behat\Mink\Element\NodeElement;
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
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The select element.
   *
   * @throws \Exception
   *   Thrown when no select field is found.
   */
  protected function findSelect($select) {
    /** @var \Behat\Mink\Element\NodeElement $element */
    $element = $this->getSession()->getPage()->find('named', ['select', $select]);

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
   * @return \Behat\Mink\Element\NodeElement[]|null
   *   An array of node elements matching the search.
   */
  protected function getTiles($region = NULL) {
    if ($region === NULL) {
      /** @var \Behat\Mink\Element\DocumentElement $regionObj */
      $regionObj = $this->getSession()->getPage();
    }
    else {
      $regionObj = $this->getRegion($region);
    }
    return $regionObj->findAll('css', '.listing__item--tile .listing__title');
  }

  /**
   * Finds a tile element by its heading.
   *
   * @param string $heading
   *   The heading of the tile to find.
   *
   * @return \Behat\Mink\Element\NodeElement|null
   *   The tile element, or null if not found.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   Thrown when the tile is not found.
   */
  protected function getTileByHeading($heading) {
    // Locate all the tiles.
    $xpath = '//*[@class and contains(concat(" ", normalize-space(@class), " "), " listing__item--tile ")]';
    // That have a heading with the specified text.
    $xpath .= '[.//*[@class and contains(concat(" ", normalize-space(@class), " "), " listing__title ")][normalize-space()="' . $heading . '"]]';

    $tile = $this->getSession()->getPage()->find('xpath', $xpath);

    if (!$tile) {
      // Throw a specific exception, so it can be catched by steps that need to
      // assert that a tile is not present.
      throw new ElementNotFoundException($this->getDriver(), "Tile '$heading'");
    }

    return $tile;
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
      'collection policy domain' => 'collection_policy_domain',
      'from' => 'group',
      'policy domain' => 'policy_domain',
      'solution policy domain' => 'solution_policy_domain',
      'solution spatial coverage' => 'solution_spatial_coverage',
      'spatial coverage' => 'spatial_coverage',
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

}
