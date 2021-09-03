<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Behat\Mink\Element\NodeElement;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use PHPUnit\Framework\Assert;

/**
 * Behat step definitions to interact with the page header.
 */
class HeaderContext extends RawDrupalContext {

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

}
