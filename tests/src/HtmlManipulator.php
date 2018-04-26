<?php

declare(strict_types = 1);

namespace Drupal\joinup;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Allows to manipulate HTML.
 */
class HtmlManipulator extends Crawler {

  /**
   * Removes the elements that match the given CSS selector from the document.
   *
   * @param string $selector
   *   The CSS selector.
   *
   * @return HtmlManipulator
   *   The object for chaining.
   */
  public function removeElements(string $selector): self {
    /** @var \DOMElement $node */
    foreach ($this->filter($selector) as $node) {
      $node->parentNode->removeChild($node);
    }
    return $this;
  }

}
