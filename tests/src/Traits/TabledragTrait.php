<?php

declare(strict_types = 1);

namespace Drupal\joinup\Traits;

use Behat\Mink\Element\NodeElement;
use Drupal\joinup\KeyboardEventKeyCodes as Key;

/**
 * Helper methods to deal with Drupal tabledrag.js elements.
 */
trait TabledragTrait {

  /**
   * Retrieves a row of a draggable table by its title.
   *
   * The code returns the first row found in the page.
   *
   * @param string $title
   *   The title of the row.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The row element.
   */
  protected function findDraggableTableRowByTitle(string $title): NodeElement {
    $xpath = '//tr[@class and contains(concat(" ", normalize-space(@class), " "), " draggable ")][.//a[text()="' . $title . '"]]';
    $row = $this->getSession()->getPage()->find('xpath', $xpath);

    if (!$row) {
      throw new \Exception("Cannot find row with title '$title'");
    }

    return $row;
  }

  /**
   * Retrieves a row of a draggable table by its position.
   *
   * The code returns the row on the first draggable table of the page.
   *
   * @param int $position
   *   The position of the row to retrieve. Positions are 1-based indexed.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The row element.
   *
   * @throws \Exception
   *   Thrown when the given table row cannot be found in the page.
   */
  protected function findDraggableTableRowByPosition(int $position): NodeElement {
    $xpath = '//tr[@class and contains(concat(" ", normalize-space(@class), " "), " draggable ")][' . $position . ']';
    $row = $this->getSession()->getPage()->find('xpath', $xpath);

    if (!$row) {
      throw new \Exception("Cannot find row with position $position.");
    }

    return $row;
  }

  /**
   * Drags a draggable row one step towards a direction.
   *
   * @param \Behat\Mink\Element\NodeElement $row
   *   The row element to drag.
   * @param string $direction
   *   The direction to move the row to. One of "up", "down", "left", "right".
   *
   * @throws \Exception
   *   Thrown when an invalid direction is specified.
   */
  protected function dragTableRowTowardsDirection(NodeElement $row, string $direction): void {
    if (!in_array($direction, ['left', 'right', 'up', 'down'])) {
      throw new \Exception('Invalid direction specified.');
    }

    $handle = $row->find('css', 'a.tabledrag-handle');
    $handle->focus();

    switch ($direction) {
      case 'left':
        $key = Key::LEFT_ARROW;
        break;

      case 'right':
        $key = Key::RIGHT_ARROW;
        break;

      case 'up':
        $key = Key::UP_ARROW;
        break;

      case 'down':
      default:
        $key = Key::DOWN_ARROW;
    }

    $handle->keyDown($key);
    $handle->keyUp($key);
    $handle->blur();
  }

}
