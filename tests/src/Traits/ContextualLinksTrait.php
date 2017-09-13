<?php

namespace Drupal\joinup\Traits;

use Behat\Mink\Element\NodeElement;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Helper methods to deal with contextual links.
 */
trait ContextualLinksTrait {

  /**
   * Find all the contextual links in a region, without the need for javascript.
   *
   * @param string $region
   *   The name of the region.
   *
   * @return array
   *   An array of links found keyed by title.
   *
   * @throws \Exception
   *   When the region is not found in the page.
   */
  protected function findContextualLinksInRegion($region) {
    return $this->findContextualLinksInElement($this->getRegion($region));
  }

  /**
   * Find all the contextual links in an element.
   *
   * @param \Behat\Mink\Element\NodeElement $element
   *   The name of the element to check.
   *
   * @return array
   *   An array of links found keyed by title.
   */
  protected function findContextualLinksInElement(NodeElement $element) {
    if (!$this->browserSupportsJavascript()) {
      return $this->generateContextualLinks($element);
    }

    return $this->findContextualLinksInJsBrowsers($element);
  }

  /**
   * Generate contextual links for a specific element in non-JS browsers.
   *
   * Contextual links are retrieved on the browser side through the use
   * of javascript, but that is not applicable for non-javascript browsers.
   *
   * @param \Behat\Mink\Element\NodeElement $element
   *   The name of the element to check.
   *
   * @return array
   *   An array of links found keyed by title.
   */
  protected function generateContextualLinks(NodeElement $element) {
    // We want to make an extra request to the website, using all the cookies
    // from the current logged in user, but doing so will change the last page
    // output, possibly breaking other steps. This can be prevented by cloning
    // the client.
    /** @var \Symfony\Component\BrowserKit\Client $client */
    $client = clone $this->getSession()->getDriver()->getClient();

    $contextual_ids = array_map(function ($element) {
      /** @var \Behat\Mink\Element\NodeElement $element */
      return $element->getAttribute('data-contextual-id');
    }, $element->findAll('xpath', '//*[@data-contextual-id]'));

    // @see Drupal.behaviors.contextual.attach(), contextual.js
    $client->request('POST', '/contextual/render', [
      'ids' => $contextual_ids,
    ]);

    $links = [];
    $response = json_decode($client->getResponse()->getContent(), TRUE);
    if ($response) {
      foreach ($contextual_ids as $id) {
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
   * Find all the contextual links in an element on JS-enabled browsers.
   *
   * @param \Behat\Mink\Element\NodeElement $element
   *   The name of the element to check.
   *
   * @return array
   *   An array of links found keyed by title.
   *
   * @throws \Exception
   *   Thrown when the contextual links are not shown within the timeframe.
   */
  protected function findContextualLinksInJsBrowsers(NodeElement $element) {
    // If the contextual link placeholder element is not there already, it
    // means that no links are available.
    if (!$element->find('css', '[data-contextual-id]')) {
      return [];
    }

    // Focus the element, so that the related contextual markup will be shown.
    $element->focus();
    // Wait until the contextual module javascript is executed. Markup will be
    // appended upon completion.
    $contextual_button = $this->waitUntil(function () use ($element) {
      return $element->find('css', '.contextual > button.trigger');
    });

    // If the contextual wrapper is not found, it means that no contextual
    // links are available for this element.
    if (!$contextual_button) {
      return [];
    }

    // Open the contextual links dropdown.
    /** @var \Behat\Mink\Element\NodeElement $contextual_button */
    $contextual_button->click();

    $link_list = $element->find('css', '.contextual ul.contextual-links');
    $visible = $this->waitUntil(function () use ($link_list) {
      return $link_list->isVisible();
    });

    if (!$visible) {
      throw new \Exception('The contextual links did not open properly within the expected time frame.');
    }

    $links = [];
    foreach ($link_list->findAll('xpath', '//a') as $link) {
      /** @var \Behat\Mink\Element\NodeElement $link */
      $links[$link->getText()] = $link->getAttribute('href');
    }

    return $links;
  }

}
