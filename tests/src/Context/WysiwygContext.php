<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\joinup\Traits\WysiwygTrait;

/**
 * Behat step definitions to test the WYSIWYG editor.
 */
class WysiwygContext extends RawDrupalContext {

  use WysiwygTrait;

  const ICON_MAPPING = [
    'image' => 'cke_button__drupalimage_icon',
  ];

  /**
   * Asserts that a given WYSIWYG editor contains a certain button.
   *
   * @param string $mapping
   *   The mapping for the button.
   * @param string $editor
   *   The label of the editor.
   *
   * @Given the :mapping icon should be available in the :editor WYSIWYG editor
   */
  public function wysiwygContainsIcon(string $mapping, string $editor): void {
    $identifier = $this->getEditorIconMapping($mapping);
    $region = $this->getWysiwyg($editor);
    $icon = $region->find('xpath', '//div[contains(concat(" ", normalize-space(@class), " "), " cke_inner ")]//span[contains(concat(" ", normalize-space(@class), " "), " ' . $identifier . ' ")]');
    if (empty($icon)) {
      throw new \Exception("No '{$mapping}' icons were found in the page.");
    }
  }

  /**
   * Maps the string to an identifier that encapsulates the icon in an editor.
   *
   * @param string $mapping
   *   The string mapping.
   *
   * @return string
   *   The identifier that the icon can be searched with.
   */
  protected function getEditorIconMapping(string $mapping): string {
    if (!in_array($mapping, array_keys(WysiwygContext::ICON_MAPPING))) {
      $mapping_keys = array_keys(WysiwygContext::ICON_MAPPING);
      $mapping_keys = implode("', '", $mapping_keys);
      throw new \InvalidArgumentException("Cannot find the '{$mapping}' mapping. Only '{$mapping_keys}' are allowed");
    }

    return WysiwygContext::ICON_MAPPING[$mapping];
  }

}
