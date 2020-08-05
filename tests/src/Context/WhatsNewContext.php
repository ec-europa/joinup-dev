<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\joinup\Traits\EntityTrait;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * Behat step definitions and related methods provided by the whats_new module.
 */
class WhatsNewContext extends RawDrupalContext {

  use EntityTrait;

  /**
   * Asserts that the support menu button has the "whats_new" class attached.
   *
   * @Then I should see the bell icon in the support menu
   */
  public function assertSupportMenuWhatsNewClass(): void {
    $element = $this->getSession()->getPage()->findById('support-menu__button');
    Assert::assertTrue($element->hasClass('whats_new'));
  }

  /**
   * Asserts that the support menu does not have the "whats_new" class attached.
   *
   * @Then I should not see the bell icon in the support menu
   */
  public function assertSupportMenuNotWhatsNewClass(): void {
    $element = $this->getSession()->getPage()->findById('support-menu__button');
    Assert::assertFalse($element->hasClass('whats_new'));
  }

  /**
   * Asserts that the support menu button has the "whats_new" class attached.
   *
   * @param string $link_title
   *   The link title.
   *
   * @Then the :link_title link should be featured as what's new
   */
  public function assertSupportMenuLinkWhatsNewClass(string $link_title): void {
    foreach ($this->getFeaturedSupportLinks() as $element) {
      if ($element->getText() === $link_title) {
        return;
      }
    }
    throw new ExpectationFailedException("No link with title '{$link_title}' has been found marked as featured in the support menu.");
  }

  /**
   * Asserts that the support menu does not have the "whats_new" class attached.
   *
   * @param string $link_title
   *   The link title.
   *
   * @Then the :link_title link should not be featured as what's new
   */
  public function assertSupportMenuLinkNotWhatsNewClass(string $link_title): void {
    foreach ($this->getFeaturedSupportLinks() as $element) {
      if ($element->getText() === $link_title) {
        throw new ExpectationFailedException("The link with title '{$link_title}' has been found marked as featured but should not.");
      }
    }
  }

  /**
   * Creates a live link menu item in the support menu.
   *
   * @param string $link
   *   The title of the link.
   * @param string $content
   *   The content entity label.
   *
   * @Given the live link with title :title pointing to :content
   */
  public function givenSupportMenuItem(string $link, string $content): void {
    $entity = $this->getEntityByLabel('node', $content);
    MenuLinkContent::create([
      'title' => $link,
      'menu_name' => 'support',
      'link' => 'internal:' . $entity->toUrl()->toString(),
      'live_link' => TRUE,
    ])->save();
  }

  /**
   * Returns a list of links from the support menu with the "whats_new" class.
   *
   * @return \Behat\Mink\Element\TraversableElement[]
   *   An array of traversable elements.
   */
  protected function getFeaturedSupportLinks(): array {
    $xpath = '//ul[contains(concat(" ", normalize-space(@class), " "), " support-menu__dropdown ")]/li[contains(concat(" ", normalize-space(@class), " "), " whats_new ")]';
    return $this->getSession()->getPage()->findAll('xpath', $xpath);
  }

}
