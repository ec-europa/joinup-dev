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

}
