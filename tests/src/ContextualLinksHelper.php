<?php

declare(strict_types = 1);

namespace Drupal\joinup;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Element\TraversableElement;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\joinup\Traits\BrowserCapabilityDetectionTrait;
use Drupal\joinup\Traits\TraversingTrait;
use Drupal\joinup\Traits\UtilityTrait;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Contains the business logic for the ContextualLinksTrait.
 *
 * Depending on whether or not a JS-capable browser is used the way contextual
 * links are handled is completely different. This class encapsulates the
 * business logic for dealing with this distinction and hides the implementation
 * details from the user. This allows us to only put the "public" methods in the
 * trait, i.e. the ones that are intended to be called in Behat code. So
 * basically this works around the limitations of traits in PHP.
 */
class ContextualLinksHelper {

  use BrowserCapabilityDetectionTrait;
  use TraversingTrait;
  use UtilityTrait;

  /**
   * The Drupal context object.
   *
   * @var \Drupal\DrupalExtension\Context\RawDrupalContext
   */
  protected $context;

  /**
   * Constructs a ContextualLinksHelper object.
   *
   * @param \Drupal\DrupalExtension\Context\RawDrupalContext $context
   *   The Drupal context.
   */
  public function __construct(RawDrupalContext $context) {
    $this->context = $context;
  }

  /**
   * Relays method calls to the context object.
   *
   * This allows us to use traits that are designed to be used in Behat context
   * classes.
   */
  public function __call($name, $arguments) {
    return $this->context->$name(...$arguments);
  }

  /**
   * Returns the paths for all the contextual links in the given element.
   *
   * The contextual links are implemented in Drupal using JavaScript, so this
   * method can only retrieve the actual data from the page when running on a JS
   * enabled browser.
   *
   * When this code is called for a non-JS browsers this will retrieve the
   * data from the Drupal site by emulating the requests that are done by the JS
   * code and returning the results.
   *
   * @param \Behat\Mink\Element\TraversableElement $element
   *   The element to check.
   *
   * @return array
   *   An array of link paths found keyed by title.
   */
  public function findContextualLinkPaths(TraversableElement $element): array {
    if (!$this->browserSupportsJavaScript()) {
      return $this->generateContextualLinks($element);
    }

    $links = [];
    foreach ($this->findContextualLinkElements($element) as $link_element) {
      /** @var \Behat\Mink\Element\NodeElement $link_element */
      $links[$link_element->getText()] = $link_element->getAttribute('href');
    }

    return $links;
  }

  /**
   * Generate contextual links for a specific element in non-JS browsers.
   *
   * Contextual links are retrieved on the browser side through the use
   * of javascript, but that is not applicable for non-javascript browsers.
   *
   * @param \Behat\Mink\Element\TraversableElement $element
   *   The name of the element to check.
   *
   * @return array
   *   An array of links found keyed by title.
   */
  protected function generateContextualLinks(TraversableElement $element): array {
    // We want to make an extra request to the website, using all the cookies
    // from the current logged in user, but doing so will change the last page
    // output, possibly breaking other steps. This can be prevented by cloning
    // the client.
    /** @var \Symfony\Component\BrowserKit\Client $client */
    $client = clone $this->getSession()->getDriver()->getClient();

    $ids = [];
    $tokens = [];
    foreach ($element->findAll('xpath', '//*[@data-contextual-id]') as $element) {
      $ids[] = $element->getAttribute('data-contextual-id');
      $tokens[] = $element->getAttribute('data-contextual-token');
    }

    // @see Drupal.behaviors.contextual.attach(), contextual.js
    $client->request('POST', base_path() . 'contextual/render', [
      'ids' => $ids,
      'tokens' => $tokens,
    ]);

    $links = [];
    $response = json_decode($client->getResponse()->getContent(), TRUE);
    if ($response) {
      foreach ($ids as $id) {
        if (isset($response[$id])) {
          $crawler = new Crawler();
          $crawler->addHtmlContent($response[$id]);

          foreach ($crawler->filterXPath('//a') as $node) {
            /** @var \DOMElement $node */
            $links[$node->nodeValue] = $node->getAttribute('href');
          }
        }
      }
    }

    return $links;
  }

  /**
   * Returns the contextual link elements that exist in the given element.
   *
   * @param \Behat\Mink\Element\TraversableElement $element
   *   The element that contains the contextual links.
   *
   * @return \Behat\Mink\Element\NodeElement[]
   *   The link elements.
   */
  protected function findContextualLinkElements(TraversableElement $element): array {
    // If the contextual link placeholder element is not there already, it
    // means that no links are available.
    if (!$element->find('css', '[data-contextual-id]')) {
      return [];
    }

    // Wait until the contextual module JavaScript is executed. Markup will be
    // appended upon completion.
    $contextual_button = $this->waitUntil(function () use ($element) {
      return $element->find('css', '.contextual > button.trigger');
    });

    // If the contextual wrapper is not found, it means that no contextual
    // links are available for this element.
    if (!$contextual_button) {
      return [];
    }

    // Open the contextual links menu if it is not yet open.
    $this->openContextualLinksMenu($element);

    $link_list = $element->find('css', '.contextual ul.contextual-links');
    return $link_list->findAll('xpath', '//a');
  }

  /**
   * Returns the contextual link element with the given link text.
   *
   * @param \Behat\Mink\Element\TraversableElement $element
   *   The element in which the contextual links reside.
   * @param string $link_text
   *   The link text of the link element to return.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The link element.
   *
   * @throws \RuntimeException
   *   Thrown when no contextual link with the given link text is found in the
   *   given element.
   */
  protected function findContextualLinkElement(TraversableElement $element, string $link_text): NodeElement {
    foreach ($this->findContextualLinkElements($element) as $link_element) {
      if (trim($link_element->getText()) === trim($link_text)) {
        return $link_element;
      }
    }

    throw new \RuntimeException("Contextual link '$link_text' not found.");
  }

  /**
   * Opens the contextual links menu for the given element.
   *
   * The contextual links are implemented in Drupal using JavaScript, so this
   * method should only be called by code that is intended to run in browsers
   * that support JS.
   *
   * @param \Behat\Mink\Element\TraversableElement $element
   *   The element that contains the contextual links to open.
   */
  protected function openContextualLinksMenu(TraversableElement $element): void {
    if (!$this->browserSupportsJavaScript()) {
      throw new \LogicException('Cannot open the contextual link menu on a browser that doesn\'t support JavaScript.');
    }

    // Wait until the contextual module JavaScript is executed. Markup will be
    // appended upon completion.
    $contextual_button = $this->waitUntil(function () use ($element): ?NodeElement {
      return $element->find('css', '.contextual > button.trigger');
    });

    // Open the contextual links menu if it is not yet open.
    $link_list = $element->find('css', '.contextual ul.contextual-links');
    if (!$link_list->isVisible()) {
      /** @var \Behat\Mink\Element\NodeElement $contextual_button */
      $contextual_button->focus();
      $contextual_button->click();

      $visible = $this->waitUntil(function () use ($link_list) {
        return $link_list->isVisible();
      });

      if (!$visible) {
        throw new \RuntimeException('The contextual links did not open properly within the expected time frame.');
      }
    }
  }

  /**
   * Clicks the contextual link with the given title in the given element.
   *
   * @param \Behat\Mink\Element\TraversableElement $element
   *   The element that contains the contextual link menu.
   * @param string $link
   *   The link title.
   */
  public function clickContextualLink(TraversableElement $element, string $link): void {
    $links = $this->findContextualLinkPaths($element);

    if (!isset($links[$link])) {
      throw new \RuntimeException("Contextual link '$link' not found.");
    }

    // If we are not in a real browser, visit the link path instead of actually
    // opening the contextual menu and clicking the link.
    if ($this->browserSupportsJavaScript()) {
      $this->openContextualLinksMenu($element);
      $link_element = $this->findContextualLinkElement($element, $link);
      $link_element->focus();
      $link_element->click();
    }
    else {
      $this->getSession()->visit($this->locatePath($links[$link]));
    }
  }

}
