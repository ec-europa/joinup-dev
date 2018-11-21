<?php

declare(strict_types = 1);

namespace Drupal\joinup\Traits;

use Behat\Mink\Element\NodeElement;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\joinup\ContextualLinksHelper;

/**
 * Helper methods to deal with contextual links.
 */
trait ContextualLinksTrait {

  /**
   * The contextual links helper class.
   *
   * @var \Drupal\joinup\ContextualLinksHelper
   */
  protected $contextualLinksHelper;

  /**
   * Returns the helper class to deal with contextual links.
   *
   * @return \Drupal\joinup\ContextualLinksHelper
   *   The helper class.
   */
  protected function getContextualLinksHelper(): ContextualLinksHelper {
    if (!$this instanceof RawDrupalContext) {
      throw new \LogicException('This trait can only be used by contexts that extend RawDrupalContext.');
    }

    if (empty($this->contextualLinksHelper)) {
      $this->contextualLinksHelper = new ContextualLinksHelper($this);
    }
    return $this->contextualLinksHelper;
  }

  /**
   * Returns the paths for all the contextual links in the given element.
   *
   * @param \Behat\Mink\Element\NodeElement $element
   *   The element to check.
   *
   * @return array
   *   An array of link paths found keyed by title.
   */
  protected function findContextualLinkPaths(NodeElement $element): array {
    return $this->getContextualLinksHelper()->findContextualLinkPaths($element);
  }

  /**
   * Clicks (or follows) the contextual link in the given element.
   *
   * Since the logic for contextual links is implemented in JavaScript this will
   * not click the contextual link but redirect the browser to the corresponding
   * page if the browser doesn't support JavaScript.
   *
   * @param \Behat\Mink\Element\NodeElement $element
   *   The element that contains the contextual links.
   * @param string $link
   *   The link title.
   */
  protected function clickContextualLink(NodeElement $element, string $link): void {
    $this->getContextualLinksHelper()->clickContextualLink($element, $link);
  }

  /**
   * Returns the contextual links button that is present in the given element.
   *
   * @param \Behat\Mink\Element\NodeElement $element
   *   The element in which the contextual links button is expected to reside.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The contextual links button.
   *
   * @throws \RuntimeException
   *   Thrown when the region or the contextual links button was not found on
   *   the page.
   */
  protected function findContextualLinkButton(NodeElement $element): NodeElement {
    // Check if the wrapper for the contextual links is present on the page.
    // Since the button is appended by the contextual.js script, we might need
    // to wait a bit before the button itself is visible.
    $button = $element->waitFor(30, function (NodeElement $element): ?NodeElement {
      return $element->find('xpath', '//div[contains(concat(" ", normalize-space(@class), " "), " contextual ")]/button');
    });

    if (!$button) {
      throw new \RuntimeException('No contextual link button found.');
    }

    return $button;
  }

}
