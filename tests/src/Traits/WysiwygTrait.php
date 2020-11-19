<?php

declare(strict_types = 1);

namespace Drupal\joinup\Traits;

use Behat\Mink\Element\TraversableElement;
use Drupal\joinup\Exception\WysiwygEditorNotFoundException;

/**
 * Helper methods for interacting with WYSIWYG editors.
 */
trait WysiwygTrait {

  /**
   * Presses the given WYSIWYG button.
   *
   * @param string $field
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
  public function pressWysiwygButton(string $field, string $button, ?TraversableElement $region = NULL): void {
    $wysiwyg = $this->getWysiwyg($field, $region);
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
   * @param string $field
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
  public function setWysiwygText(string $field, string $text, ?TraversableElement $region = NULL): void {
    $wysiwyg = $this->getWysiwyg($field, $region);
    $textarea_elements = $wysiwyg->findAll('xpath', '//textarea');
    if (empty($textarea_elements)) {
      throw new \Exception("Could not find the textarea for the '$field' field.");
    }
    if (count($textarea_elements) > 1) {
      throw new \Exception("Multiple textareas found for '$field'.");
    }
    $textarea = reset($textarea_elements);
    $textarea->setValue($text);
  }

  /**
   * Checks whether a WYSIWYG editor with the given field label is present.
   *
   * @param string $field
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
  public function hasWysiwyg(string $field, ?TraversableElement $region = NULL): bool {
    try {
      $this->getWysiwyg($field, $region);
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
   * @param string $field
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
  protected function getWysiwyg(string $field, ?TraversableElement $region = NULL): TraversableElement {
    if (empty($region)) {
      $region = $this->getSession()->getPage();
    }

    $label_elements = $region->findAll('xpath', '//label[text()="' . $field . '"]');
    if (empty($label_elements)) {
      throw new WysiwygEditorNotFoundException("Could not find the '$field' field label.");
    }

    if (count($label_elements) > 1) {
      throw new \Exception("Found multiple instances of the '$field' field label.");
    }

    $label_element = reset($label_elements);
    $wysiwyg_id = 'cke_' . $label_element->getAttribute('for');

    $wysiwyg_elements = $region->findAll('xpath', '//div[@id="' . $wysiwyg_id . '"]');
    if (empty($wysiwyg_elements)) {
      throw new WysiwygEditorNotFoundException("Could not find the '$field' wysiwyg editor.");
    }

    if (count($wysiwyg_elements) > 1) {
      throw new \Exception("Found multiple wysiwyg editors with the '$field' field label.");
    }

    return reset($wysiwyg_elements);
  }

}
