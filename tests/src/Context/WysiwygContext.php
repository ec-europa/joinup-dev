<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\joinup\Traits\BrowserCapabilityDetectionTrait;
use Drupal\joinup\Traits\UtilityTrait;
use Drupal\joinup\Traits\WysiwygTrait;
use PHPUnit\Framework\Assert;

/**
 * Behat step definitions for interacting with WYSIWYG editors.
 */
class WysiwygContext extends RawDrupalContext {

  use BrowserCapabilityDetectionTrait;
  use UtilityTrait;
  use WysiwygTrait;

  /**
   * Enters the given text in the given WYSIWYG editor.
   *
   * If this is running on a JavaScript enabled browser it will first click the
   * 'Source' button so the text can be entered as normal HTML.
   *
   * @param string $text
   *   The text to enter in the WYSIWYG editor.
   * @param string $label
   *   The label of the field containing the WYSIWYG editor.
   *
   * @When I enter :text in the :label wysiwyg editor
   */
  public function enterTextInWysiwyg($text, $label) {
    // If we are running in a JavaScript enabled browser, first click the
    // 'Source' button so we can enter the text as HTML and get the same result
    // as in a non-JS browser.
    if ($this->browserSupportsJavaScript()) {
      $this->pressWysiwygButton($label, 'Source');
      $this->setWysiwygText($label, $text);
    }
    else {
      $this->getSession()->getPage()->fillField($label, $text);
    }
  }

  /**
   * Presses a button in a given WYSIWYG editor.
   *
   * @param string $button
   *   The label of the button to press.
   * @param string $label
   *   The label of the field containing the WYSIWYG editor.
   *
   * @Then I press the button :button in the :label wysiwyg editor
   */
  public function pressButtonInWysiwyg($button, $label) {
    self::assertJavaScriptEnabledBrowser();

    $this->pressWysiwygButton($label, $button);
  }

  /**
   * Checks that a given field label is associated with a WYSIWYG editor.
   *
   * @param string $label
   *   The label of the field containing the WYSIWYG editor.
   *
   * @Then I should see the :label wysiwyg editor
   */
  public function assertWysiwyg($label) {
    Assert::assertTrue($this->hasWysiwyg($label));
  }

  /**
   * Checks that a given field label is not associated with a WYSIWYG editor.
   *
   * @param string $label
   *   The label of the field uncontaining the WYSIWYG editor.
   *
   * @Then the :label field should not have a wysiwyg editor
   */
  public function assertNoWysiwyg($label) {
    Assert::assertFalse($this->hasWysiwyg($label));
  }

  /**
   * Asserts that a ckeditor list contains an element.
   *
   * CKeditor stores the lists available in the header of the editor pane in an
   * iframe with a role tag. The dropdown "Format" has to be clicked prior to
   * having the iframe available.
   *
   * @param string $field
   *   The name of the field that contains the Wysiwyg editor to check.
   * @param string $format_tags
   *   Comma-separated list of paragraph formats to check.
   *
   * @throws \Exception
   *   Thrown when the formats were found in the format list.
   *
   * @Then the paragraph formats in the :field field should not contain the :format_tags format(s)
   */
  public function assertNotFormatInCkeditorExists($field, $format_tags) {
    if (!$this->browserSupportsJavaScript()) {
      throw new \Exception('This step requires javascript to run.');
    }
    $element = $this->getSession()->getPage()->findField($field);
    $element_id = $element->getAttribute('id');
    $format_tags = $this->explodeCommaSeparatedStepArgument($format_tags);
    $has_tags_condition = <<<JS
      return CKEDITOR.instances["$element_id"].config.format_tags;
JS;
    $found_tags = $this->getSession()->getDriver()->evaluateScript($has_tags_condition);
    $found_tags_array = explode(';', $found_tags);
    $invalid_tags = array_intersect($format_tags, $found_tags_array);
    if (!empty($invalid_tags)) {
      throw new \Exception(sprintf('The following elements were found in the format list but should not: %s.', implode(', ', $invalid_tags)));
    }
  }

}
