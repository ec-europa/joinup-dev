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
  public function enterTextInWysiwyg(string $text, string $label): void {
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
  public function pressButtonInWysiwyg(string $button, string $label): void {
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
  public function assertWysiwyg(string $label): void {
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
  public function assertNoWysiwyg(string $label): void {
    Assert::assertFalse($this->hasWysiwyg($label));
  }

  /**
   * Asserts that a ckeditor list contains an element.
   *
   * CKeditor stores the lists available in the header of the editor pane in an
   * iframe with a role tag. The dropdown "Format" has to be clicked prior to
   * having the iframe available.
   *
   * @param string $label
   *   The name of the field that contains the Wysiwyg editor to check.
   * @param string $format_tags
   *   Comma-separated list of paragraph formats to check.
   *
   * @throws \Exception
   *   Thrown when the formats were found in the format list.
   *
   * @Then the paragraph formats in the :field field should not contain the :format_tags format(s)
   */
  public function assertNotFormatInCkeditorExists(string $label, string $format_tags): void {
    if (!$this->browserSupportsJavaScript()) {
      throw new \Exception('This step requires javascript to run.');
    }
    $element = $this->getSession()->getPage()->findField($label);
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

  /**
   * Checks that a WYSIWYG editor has the correct buttons.
   *
   * This works by discovering the editor type in the HTML, and then loading the
   * configuration from the database. This makes sure it also works in non-JS
   * browsers.
   *
   * @param string $label
   *   The label of the field containing the WYSIWYG editor to inspect.
   * @param string $buttons
   *   A comma-separated list of buttons that should be present, in the correct
   *   order.
   *
   * @Then the :label wysiwyg editor should have the buttons :buttons
   */
  public function assertWysiwygButtons(string $label, string $buttons) {
    /** @var \Drupal\ckeditor\CKEditorPluginManager $ckeditor_plugin_manager */
    $ckeditor_plugin_manager = \Drupal::service('plugin.manager.ckeditor.plugin');

    $editor = $this->getWysiwygEditorEntity($label);

    // Retrieve the buttons that are available for this editor instance, but
    // filter out the separators. We are only concerned with the buttons.
    $enabled_buttons = array_filter($ckeditor_plugin_manager->getEnabledButtons($editor), function (string $id): bool {
      return $id !== '-';
    });

    // Replace the button IDs with human readable labels.
    $button_data = array_reduce($ckeditor_plugin_manager->getButtons(), 'array_merge', []);
    $enabled_buttons = array_map(function (string $id) use ($button_data) {
      return (string) $button_data[$id]['label'];
    }, $enabled_buttons);

    $expected_buttons = array_map('trim', explode(',', $buttons));
    Assert::assertEquals(array_values($expected_buttons), array_values($enabled_buttons));
  }

}
