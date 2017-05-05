<?php

namespace Drupal\joinup_core\Element;

use Drupal\Core\Datetime\Element\Datetime as CoreDatetime;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a datetime element.
 */
class Datetime extends CoreDatetime {

  /**
   * {@inheritdoc}
   *
   * We are merely adding a different error message on the field.
   */
  public static function validateDatetime(&$element, FormStateInterface $form_state, &$complete_form) {
    $input_exists = FALSE;
    $input = NestedArray::getValue($form_state->getValues(), $element['#parents'], $input_exists);
    if ($input_exists) {
      $title = '';
      if (!empty($element['#title'])) {
        $title = $element['#title'];
      }
      else {
        $parents = $element['#array_parents'];
        array_pop($parents);
        $parent_element = NestedArray::getValue($complete_form, $parents);
        if (!empty($parent_element['#title'])) {
          $title = $parent_element['#title'];
        }
      }
      $date_format = $element['#date_date_element'] != 'none' ? static::getHtml5DateFormat($element) : '';
      $time_format = $element['#date_time_element'] != 'none' ? static::getHtml5TimeFormat($element) : '';
      $format = trim($date_format . ' ' . $time_format);

      // If there's empty input and the field is not required, set it to empty.
      if (empty($input['date']) && empty($input['time']) && !$element['#required']) {
        $form_state->setValueForElement($element, NULL);
      }
      // If there's empty input and the field is required, set an error. A
      // reminder of the required format in the message provides a good UX.
      elseif (empty($input['date']) && empty($input['time']) && $element['#required']) {
        $form_state->setError($element, t('The %field date is required. Please enter a date in the format %format.', ['%field' => $title, '%format' => static::formatExample($format)]));
      }
      else {
        // If the date is valid, set it.
        $date = $input['object'];
        if ($date instanceof DrupalDateTime && !$date->hasErrors()) {
          $form_state->setValueForElement($element, $date);
        }
        // If only one of the two fields are filled, set an error.
        // @see: https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3194.
        elseif (isset($input['date']) && isset($input['time']) && (empty($input['date']) xor empty($input['time']))) {
          $form_state->setError($element, t('The date and time should both be entered in the %field field.', ['%field' => $title]));
        }
        // If the date is invalid, set an error. A reminder of the required
        // format in the message provides a good UX.
        else {
          $form_state->setError($element, t('The %field date is invalid. Please enter a date in the format %format.', ['%field' => $title, '%format' => static::formatExample($format)]));
        }
      }
    }
  }

}
