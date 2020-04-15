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
   *   (optional) Limit the region to the specific element. Defaults to the full
   *   page.
   *
   * @throws \Exception
   *   Thrown when the button is not found, or if there are multiple buttons
   *   with the same title.
   */
  public function pressWysiwygButton(string $field, string $button, ?TraversableElement $region = NULL): void {
    if (empty($region)) {
      $region = $this->getSession()->getPage();
    }

    $wysiwyg = $this->getWysiwyg($field, $region);
    $button_element = $wysiwyg->find('xpath', '//a[@title="' . $button . '"]');
    if (empty($button_element)) {
      throw new \Exception("Could not find the '$button' button.");
    }

    $button_element->click();
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
   *   (optional) Limit the region to the specific element. Defaults to the full
   *   page.
   *
   * @throws \Exception
   *   Thrown when the textarea could not be found or if there are multiple
   *   textareas.
   */
  public function setWysiwygText(string $field, string $text, ?TraversableElement $region = NULL): void {
    if (empty($region)) {
      $region = $this->getSession()->getPage();
    }

    $wysiwyg = $this->getWysiwyg($field, $region);
    $textarea_element = $wysiwyg->find('xpath', '//textarea');
    if (empty($textarea_element)) {
      throw new \Exception("Could not find the textarea for the '$field' field.");
    }
    $textarea_element->setValue($text);
  }

  /**
   * Checks whether a WYSIWYG editor with the given field label is present.
   *
   * @param string $field
   *   The label of the field to which the WYSIWYG editor is attached.
   *
   * @return bool
   *   TRUE if the editor is present, FALSE otherwise.
   */
  public function hasWysiwyg(string $field): bool {
    try {
      $this->getWysiwyg($field, $this->getSession()->getPage());
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
   *   (optional) Limit the region to the specific element. Defaults to the full
   *   page.
   *
   * @return \Behat\Mink\Element\TraversableElement
   *   The WYSIWYG editor.
   *
   * @throws \Exception
   *   When the field label can not be found, or the field label or editor is
   *   present more than once in the page.
   * @throws \Drupal\joinup\Exception\WysiwygEditorNotFoundException
   *   Thrown when the wysiwyg editor can not be found in the page.
   */
  protected function getWysiwyg(string $field, TraversableElement $region): TraversableElement {
    $label_element = $region->find('xpath', '//label[text()="' . $field . '"]');
    if (empty($label_element)) {
      throw new \Exception("Could not find the '$field' field label.");
    }

    $wysiwyg_id = 'cke_' . $label_element->getAttribute('for');
    $wysiwyg_element = $region->find('xpath', '//div[@id="' . $wysiwyg_id . '"]');
    if (empty($wysiwyg_element)) {
      throw new WysiwygEditorNotFoundException("Could not find the '$field' wysiwyg editor.");
    }

    return $wysiwyg_element;
  }

}
