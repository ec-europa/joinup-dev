<?php

declare(strict_types = 1);

namespace Drupal\joinup\Traits;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Element\TraversableElement;
use Drupal\editor\Entity\Editor;
use Drupal\joinup\Exception\WysiwygEditorNotFoundException;

/**
 * Helper methods for interacting with WYSIWYG editors.
 */
trait WysiwygTrait {

  /**
   * Presses the given WYSIWYG button.
   *
   * @param string $label
   *   The field label of the field to which the WYSIWYG editor is attached. For
   *   example 'Body'.
   * @param string $button
   *   The title of the button to click.
   * @param \Behat\Mink\Element\TraversableElement|null $region
   *   (optional) The region where the editor is expected to be located.
   *   Defaults to the entire page.
   *
   * @throws \Exception
   *   Thrown when the button is not found, or if there are multiple buttons
   *   with the same title.
   */
  public function pressWysiwygButton(string $label, string $button, ?TraversableElement $region = NULL): void {
    $wysiwyg = $this->getWysiwyg($label, $region);
    $button_elements = $wysiwyg->findAll('xpath', '//a[@title="' . $button . '"]');
    if (empty($button_elements)) {
      throw new \Exception("Could not find the '$button' button.");
    }
    if (count($button_elements) > 1) {
      throw new \Exception("Multiple '$button' buttons found in the editor.");
    }
    $button = reset($button_elements);
    $button->click();
  }

  /**
   * Enters the given text in the textarea of the specified WYSIWYG editor.
   *
   * If there is any text existing it will be replaced.
   *
   * @param string $label
   *   The field label of the field to which the WYSIWYG editor is attached. For
   *   example 'Body'.
   * @param string $text
   *   The text to enter in the textarea.
   * @param \Behat\Mink\Element\TraversableElement|null $region
   *   (optional) The region where the editor is expected to be located.
   *   Defaults to the entire page.
   *
   * @throws \Exception
   *   Thrown when the textarea could not be found or if there are multiple
   *   textareas.
   */
  public function setWysiwygText(string $label, string $text, ?TraversableElement $region = NULL): void {
    $wysiwyg = $this->getWysiwyg($label, $region);
    $textarea_elements = $wysiwyg->findAll('xpath', '//textarea');
    if (empty($textarea_elements)) {
      throw new \Exception("Could not find the textarea for the '$label' field.");
    }
    if (count($textarea_elements) > 1) {
      throw new \Exception("Multiple textareas found for '$label'.");
    }
    $textarea = reset($textarea_elements);
    $textarea->setValue($text);
  }

  /**
   * Checks whether a WYSIWYG editor with the given field label is present.
   *
   * @param string $label
   *   The label of the field to which the WYSIWYG editor is attached.
   * @param \Behat\Mink\Element\TraversableElement|null $region
   *   (optional) The region where the editor is expected to be located.
   *   Defaults to the entire page.
   *
   * @return bool
   *   TRUE if the editor is present, FALSE otherwise.
   *
   * @throws \Exception
   *   Thrown when the field label or editor is present more than once.
   */
  public function hasWysiwyg(string $label, ?TraversableElement $region = NULL): bool {
    try {
      $this->getWysiwyg($label, $region);
      return TRUE;
    }
    // Only catch the specific exception that is thrown when the WYSIWYG editor
    // is not present, let all other exceptions pass through.
    catch (WysiwygEditorNotFoundException $e) {
      return FALSE;
    }
  }

  /**
   * Returns the WYSIWYG editor that is associated with the given field label.
   *
   * This is hardcoded on the CKE editor which is included with Drupal core.
   *
   * @param string $label
   *   The label of the field to which the WYSIWYG editor is attached.
   * @param \Behat\Mink\Element\TraversableElement|null $region
   *   (optional) The region where the editor is expected to be located.
   *   Defaults to the entire page.
   *
   * @return \Behat\Mink\Element\TraversableElement
   *   The WYSIWYG editor.
   *
   * @throws \Drupal\joinup\Exception\WysiwygEditorNotFoundException
   *   Thrown when the field label or wysiwyg editor is not found.
   */
  protected function getWysiwyg(string $label, ?TraversableElement $region = NULL): TraversableElement {
    if (empty($region)) {
      $region = $this->getSession()->getPage();
    }

    $label_elements = $region->findAll('xpath', '//label[text()="' . $label . '"]');
    if (empty($label_elements)) {
      throw new WysiwygEditorNotFoundException("Could not find the '$label' field label.");
    }

    if (count($label_elements) > 1) {
      throw new \Exception("Found multiple instances of the '$label' field label.");
    }

    $label_element = reset($label_elements);
    $wysiwyg_id = 'cke_' . $label_element->getAttribute('for');

    $wysiwyg_elements = $region->findAll('xpath', '//div[@id="' . $wysiwyg_id . '"]');
    if (empty($wysiwyg_elements)) {
      throw new WysiwygEditorNotFoundException("Could not find the '$label' wysiwyg editor.");
    }

    if (count($wysiwyg_elements) > 1) {
      throw new \Exception("Found multiple wysiwyg editors with the '$label' field label.");
    }

    return reset($wysiwyg_elements);
  }

  /**
   * Returns the Editor entity that is associated with the given field label.
   *
   * @param string $label
   *   The label of the field to which the WYSIWYG editor is attached.
   * @param \Behat\Mink\Element\TraversableElement|null $region
   *   (optional) The region where the editor is expected to be located.
   *   Defaults to the entire page.
   *
   * @return \Drupal\editor\Entity\Editor
   *   The Editor entity.
   *
   * @throws \Drupal\joinup\Exception\WysiwygEditorNotFoundException
   *   Thrown when the field label or wysiwyg editor is not found.
   */
  protected function getWysiwygEditorEntity(string $label, ?TraversableElement $region = NULL): Editor {
    if (empty($region)) {
      $region = $this->getSession()->getPage();
    }

    // Find the hidden editor select input element.
    $editor_select_element = $region->find('xpath', '//textarea[@id=//label[text()="' . $label . '"]/@for]/ancestor::*[contains(concat(" ", normalize-space(@class), " "), " form-wrapper ")][position() = 1]//input[@type = "hidden"]');
    if (!$editor_select_element instanceof NodeElement) {
      throw new WysiwygEditorNotFoundException("Could not find the '$label' wysiwyg editor.");
    }

    $editor_id = $editor_select_element->getValue();
    if (empty($editor_id)) {
      throw new WysiwygEditorNotFoundException("Could not find the machine name of the '$label' wysiwyg editor.");
    }
    $editor = Editor::load($editor_id);
    if (!$editor instanceof Editor) {
      throw new WysiwygEditorNotFoundException("Could not load the editor entity for the '$label' wysiwyg editor.");
    }

    return $editor;
  }

  /**
   * Returns the buttons that are shown in the editor with the given label.
   *
   * Note that this returns different labels depending on whether or not the
   * browser supports JavaScript. For example a non-JS browser will return
   * 'Blockquote' while a JS browser will return 'Block Quote'.
   *
   * @param string $label
   *   The label of the field to which the WYSIWYG editor is attached.
   * @param \Behat\Mink\Element\TraversableElement|null $region
   *   (optional) The region where the editor is expected to be located.
   *   Defaults to the entire page.
   *
   * @return string[]
   *   An array of button labels.
   *
   * @throws \Drupal\joinup\Exception\WysiwygEditorNotFoundException
   *   Thrown when the WYSIWYG editor with the given label is not found in the
   *   page.
   */
  protected function getWysiwygButtons(string $label, ?TraversableElement $region = NULL): array {
    assert(method_exists($this, 'browserSupportsJavaScript'), __METHOD__ . ' depends on BrowserCapabilityDetectionTrait. Please include it in your class.');

    if ($this->browserSupportsJavaScript()) {
      $editor = $this->getWysiwyg($label, $region);
      $button_elements = $editor->findAll('xpath', '//a[contains(concat(" ", normalize-space(@class), " "), " cke_button ")]/span[contains(@id, "_label")]');

      $buttons = array_map(function (NodeElement $element): string {
        return trim($element->getHtml());
      }, $button_elements);
    }
    else {
      // When no JS is available the editor is not loaded in the page. Retrieve
      // the buttons from the editor config.
      /** @var \Drupal\ckeditor\CKEditorPluginManager $ckeditor_plugin_manager */
      $ckeditor_plugin_manager = \Drupal::service('plugin.manager.ckeditor.plugin');
      $editor = $this->getWysiwygEditorEntity($label);

      // Retrieve the buttons that are available for this editor instance, but
      // filter out the separators. We are only concerned with the buttons.
      $buttons = array_filter($ckeditor_plugin_manager->getEnabledButtons($editor), function (string $id): bool {
        return $id !== '-';
      });

      // Replace the button IDs with human readable labels.
      $button_data = array_reduce($ckeditor_plugin_manager->getButtons(), 'array_merge', []);
      $buttons = array_map(function (string $id) use ($button_data) {
        return (string) $button_data[$id]['label'];
      }, $buttons);
    }

    return $buttons;
  }

}
