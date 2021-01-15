<?php

declare(strict_types = 1);

namespace Drupal\joinup;

use Behat\Mink\Element\NodeElement;

/**
 * Wraps a Material Design "chip" element.
 *
 * Chips are depending on JavaScript for their full functionality but they can
 * also have some limited interaction done when JavaScript is disabled, such as
 * verifying their presence or content.
 *
 * This class wraps the Mink NodeElements representing the actual chips and
 * allows to interact with them, regardless of whether they are represented in
 * the DOM as a visible text element (with JS enabled) or as a hidden form
 * element (with JS disabled).
 */
class Chip {

  /**
   * The Mink element representing the chip in the document.
   *
   * @var \Behat\Mink\Element\NodeElement
   */
  protected $element;

  /**
   * Whether or not the element can be visually interacted with.
   *
   * @var bool
   */
  protected $isVisible;

  /**
   * Constructs a new Chip.
   *
   * @param \Behat\Mink\Element\NodeElement $element
   *   The Mink element that represents the chip in the document.
   * @param bool $isVisible
   *   Whether or not the element can be visually interacted with.
   */
  public function __construct(NodeElement $element, bool $isVisible) {
    $this->element = $element;
    $this->isVisible = $isVisible;
  }

  /**
   * Returns the chip text.
   *
   * @return string
   *   The chip text.
   */
  public function getText(): string {
    return $this->isVisible() ? $this->element->getText() : $this->element->getAttribute('data-description');
  }

  /**
   * Returns whether or not the chip can be visually interacted with.
   *
   * @return bool
   *   TRUE if the chip can be visually interacted with.
   */
  public function isVisible(): bool {
    return $this->isVisible;
  }

  /**
   * Returns the remove button.
   *
   * @return \Behat\Mink\Element\NodeElement|null
   *   The element representing the remove button, or NULL if no remove button
   *   is present on the chip.
   */
  public function getRemoveButton(): ?NodeElement {
    $xpath = "/following-sibling::button[contains(concat(' ', normalize-space(@class), ' '), ' mdl-chip__action ')]";
    return $this->element->find('xpath', $xpath);
  }

}
