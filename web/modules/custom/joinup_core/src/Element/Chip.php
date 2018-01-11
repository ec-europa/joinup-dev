<?php

namespace Drupal\joinup_core\Element;

use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a form element for the Material design "chip" component.
 *
 * The #validate, #submit, #ajax and #limit_validation_errors properties will
 * be moved to the button.
 *
 * Properties:
 * - #text: the text to use in the chip element.
 *
 * Usage Example:
 * @code
 * $form['chip'] = array(
 *   '#type' => 'chip',
 *   '#text' => $this->t('Example'),
 *   '#submit' => ['::elementSubmit'],
 * );
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Submit
 *
 * @FormElement("chip")
 */
class Chip extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return [
      '#input' => FALSE,
      '#process' => [
        [$class, 'processChip'],
      ],
      '#theme' => 'chip',
    ];
  }

  /**
   * Processes a chip element.
   *
   * @param array $element
   *   An associative array containing the element.
   *
   * @return array
   *   The processed element.
   */
  public static function processChip(array &$element) {
    $element['#tree'] = TRUE;
    $element['remove'] = [
      '#type' => 'submit',
      '#value' => t('Remove'),
      '#name' => 'remove_' . $element['#name'],
      // Render the submit as a <button> element.
      '#theme_wrappers' => ['input__chip_button'],
    ];

    // Move some properties to the button.
    $properties = ['#validate', '#submit', '#ajax', '#limit_validation_errors'];
    foreach ($properties as $property) {
      if (isset($element[$property])) {
        $element['remove'][$property] = $element[$property];
        unset($element[$property]);
      }
    }

    return $element;
  }

}
