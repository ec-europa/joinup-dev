<?php

namespace Drupal\joinup\Traits;

use Behat\Mink\Element\NodeElement;

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
   */
  protected function getTileByHeading($heading) {
    // Locate all the tiles.
    $xpath = '//*[@class and contains(concat(" ", normalize-space(@class), " "), " listing__item--tile ")]';
    // That have a heading with the specified text.
    $xpath .= '[.//*[@class and contains(concat(" ", normalize-space(@class), " "), " listing__title ")][normalize-space()="' . $heading . '"]]';

    return $this->getSession()->getPage()->find('xpath', $xpath);
  }

}
