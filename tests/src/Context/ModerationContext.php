<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\joinup\Traits\EntityTrait;

/**
 * Behat step definitions for testing moderation.
 */
class ModerationContext extends RawDrupalContext {

  use EntityTrait;

  /**
   * Checks that the moderation preview for the given node contains the text.
   *
   * @param string $title
   *   The title of the node of which to check the moderation preview.
   * @param string $text
   *   The text that is expected to be present.
   *
   * @Then the moderation preview of :title should contain the text :text
   */
  public function assertModerationPreviewContainsText($title, $text) {
    $node = $this->getEntityByLabel('node', $title);
    $xpath = '//h2/a[@href = "' . $node->toUrl('canonical', [
      'base_url' => rtrim($GLOBALS['base_path'], '/'),
    ])->toString() . '"]/ancestor::article';
    $this->assertSession()->elementTextContains('xpath', $xpath, $text);
  }

  /**
   * Clicks the given link in the moderation preview of the given node.
   *
   * @param string $link_text
   *   The text of the link to click.
   * @param string $title
   *   The title of the node for which to click the link.
   *
   * @When I click the :link_text link in the :title moderation preview
   */
  public function clickLinkInModerationPreview($link_text, $title) {
    $node = $this->getEntityByLabel('node', $title);
    $xpath = '//h2/a[@href = "' . $node->toUrl('canonical', [
      'base_url' => rtrim($GLOBALS['base_path'], '/'),
    ])->toString() . '"]/ancestor::article//a[text() = "' . $link_text . '"]';
    $this->assertSession()->elementExists('xpath', $xpath)->click();
  }

}
