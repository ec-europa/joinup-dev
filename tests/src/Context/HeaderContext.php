<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use PHPUnit\Framework\Assert;

/**
 * Behat step definitions to interact with the page header.
 */
class HeaderContext extends RawDrupalContext {

  /**
   * Maps HTML element names to human readable aliases.
   */
  protected const HTML_ELEMENT_ALIASES = [
    'link' => 'a',
    'button' => 'button',
  ];

  /**
   * Checks that the Joinup logo is present in the navigation bar.
   *
   * @Then I should see the Joinup logo in the navigation bar
   */
  public function assertLogo(): void {
    $link_element = $this->getSession()->getPage()->find('css', 'nav.joinup-navbar a.navbar-brand');
    Assert::assertInstanceOf(NodeElement::class, $link_element, 'A link for the logo is present in the navbar.');
    Assert::assertEquals(base_path(), $link_element->getAttribute('href'), 'The logo links to the homepage.');
    $logo_element = $link_element->find('css', 'img.navbar-brand-image');
    Assert::assertInstanceOf(NodeElement::class, $logo_element, 'A logo is present in the navbar.');
    Assert::assertEquals('Joinup logo', $logo_element->getAttribute('alt'), 'The logo has an alt text.');
    $expected_logo_uri = base_path() . drupal_get_path('theme', 'ventuno') . '/src/images/logo.svg';
    Assert::assertEquals($expected_logo_uri, $logo_element->getAttribute('src'), 'The Joinup logo is shown in the navbar.');
  }

  /**
   * Checks the content of the main navigation on desktop and mobile.
   *
   * @param \Behat\Gherkin\Node\TableNode $items
   *   The expected items that are present in the main navigation bar.
   *
   * @Then I should see the following items in the main navigation:
   */
  public function assertMainNavigationItems(TableNode $items): void {
    // Retrieve the main navigation links on desktop.
    $desktop_items = $this->getSession()->getPage()->findAll('xpath', '//nav[contains(concat(" ", normalize-space(@class), " "), " joinup-navbar ")]//ul[contains(concat(" ", normalize-space(@class), " "), " navbar-nav ")]/li/*[self::a or self::button]');
    // Retrieve the main navigation links on mobile.
    $mobile_items = $this->getSession()->getPage()->findAll('css', 'nav.joinup-navbar ul.accordion-list a');

    foreach ($items->getColumnsHash() as $item) {
      // Check if the item is present in the desktop menu.
      if ($item['desktop menu'] !== 'not shown') {
        $actual_item = array_shift($desktop_items);

        // Check that the item exists.
        Assert::assertInstanceOf(NodeElement::class, $actual_item, sprintf('Expected the %s item in the main navigation menu but instead found nothing.', $item['link']));

        // Check that the item is the expected type.
        $actual_tag = $actual_item->getTagName();
        Assert::assertEquals(self::HTML_ELEMENT_ALIASES[$item['desktop menu']], $actual_tag, sprintf('Expected that the %s item in the main navigation menu was a %s but instead found a %s element.', $item['link'], $item['desktop menu'], $actual_tag));

        // Check the item text.
        $actual_text = trim($actual_item->getHtml());
        Assert::assertEquals($item['link'], $actual_text, sprintf('Expected the %s item in the main navigation menu but instead found %s.', $item['link'], $actual_text));
      }

      // Check the hamburger menu.
      if ($item['hamburger menu'] !== 'not shown') {
        $actual_item = array_shift($mobile_items);

        // Check that the item exists.
        Assert::assertInstanceOf(NodeElement::class, $actual_item, sprintf('Expected the %s item in the hamburger menu but instead found nothing.', $item['link']));

        // Check that the item is the expected type.
        $actual_tag = $actual_item->getTagName();
        Assert::assertEquals(self::HTML_ELEMENT_ALIASES[$item['hamburger menu']], $actual_tag, sprintf('Expected that the %s item in the hamburger menu was a %s but instead found a %s element.', $item['link'], $item['hamburger menu'], $actual_tag));

        // Check the item text.
        $actual_text = trim($actual_item->getHtml());
        Assert::assertEquals($item['link'], $actual_text, sprintf('Expected the %s item in the hamburger menu but instead found %s.', $item['link'], $actual_text));
      }
    }

    Assert::assertEmpty($desktop_items);
    Assert::assertEmpty($mobile_items);
  }

}
