<?php

namespace Drupal\rdf_file\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\file\Entity\File;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;
use Drupal\rdf_file\Entity\RemoteFile;

/**
 * Plugin implementation of the 'file_generic' widget.
 *
 * @FieldWidget(
 *   id = "rdf_file_generic",
 *   label = @Translation("File"),
 *   field_types = {
 *     "rdf_file"
 *   }
 * )
 */
class RdfFileWidget extends FileWidget {

  /**
   * Overrides \Drupal\Core\Field\WidgetBase::formMultipleElements().
   *
   * Special handling for draggable multiple widgets and 'add more' button.
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $parents = $form['#parents'];

    // Load the items for form rebuilds from the field state as they might not
    // be in $form_state->getValues() because of validation limitations. Also,
    // they are only passed in as $items when editing existing entities.
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    if (isset($field_state['items'])) {
      $items->setValue($field_state['items']);
    }

    // Determine the number of widgets to display.
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()
      ->getCardinality();
    switch ($cardinality) {
      case FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED:
        $max = count($items);
        $is_multiple = TRUE;
        break;

      default:
        $max = $cardinality - 1;
        $is_multiple = ($cardinality > 1);
        break;
    }

    $title = $this->fieldDefinition->getLabel();
    $description = $this->getFilteredDescription();

    $elements = array();

    $delta = 0;
    // Add an element for every existing item.
    foreach ($items as $item) {
      $element = array(
        '#title' => $title,
        '#description' => $description,
      );
      $element = $this->formSingleElement($items, $delta, $element, $form, $form_state);

      if ($element) {
        // Input field for the delta (drag-n-drop reordering).
        if ($is_multiple) {
          // We name the element '_weight' to avoid clashing with elements
          // defined by widget.
          $element['_weight'] = array(
            '#type' => 'weight',
            '#title' => t('Weight for row @number', array('@number' => $delta + 1)),
            '#title_display' => 'invisible',
            // Note: this 'delta' is the FAPI #type 'weight' element's property.
            '#delta' => $max,
            '#default_value' => $item->_weight ?: $delta,
            '#weight' => 100,
          );
        }

        $elements[$delta] = $element;
        $delta++;
      }
    }

    $empty_single_allowed = ($cardinality == 1 && $delta == 0);
    $empty_multiple_allowed = ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED || $delta < $cardinality) && !$form_state->isProgrammed();

    // Add one more empty row for new uploads except when this is a programmed
    // multiple form as it is not necessary.
    if ($empty_single_allowed || $empty_multiple_allowed) {
      // Create a new empty item.
      $items->appendItem();
      $element = array(
        '#title' => $title,
        '#description' => $description,
      );
      $element = $this->formSingleElement($items, $delta, $element, $form, $form_state);
      if ($element) {
        $element['#required'] = ($element['#required'] && $delta == 0);
        $elements[$delta] = $element;
      }
    }

    if ($is_multiple) {
      // The group of elements all-together need some extra functionality after
      // building up the full list (like draggable table rows).
      $elements['#file_upload_delta'] = $delta;
      $elements['#type'] = 'details';
      $elements['#open'] = TRUE;
      $elements['#theme'] = 'rdf_file_widget_multiple';
      $elements['#theme_wrappers'] = array('details');
      $elements['#process'] = array(array(get_class($this), 'processMultiple'));
      $elements['#title'] = $title;

      $elements['#description'] = $description;
      $elements['#field_name'] = $field_name;
      $elements['#language'] = $items->getLangcode();
      // The field settings include defaults for the field type. However, this
      // widget is a base class for other widgets (e.g., ImageWidget) that may
      // act on field types without these expected settings.
      $field_settings = $this->getFieldSettings() + array('display_field' => NULL);
      $elements['#display_field'] = (bool) $field_settings['display_field'];

      // Add some properties that will eventually be added to the file upload
      // field. These are added here so that they may be referenced easily
      // through a hook_form_alter().
      $elements['#file_upload_title'] = t('Add a new file');
    }

    if ($elements) {
      $elements += array(
        '#theme' => 'field_multiple_value_form',
        '#field_name' => $field_name,
        '#cardinality' => $cardinality,
        '#cardinality_multiple' => $this->fieldDefinition->getFieldStorageDefinition()
          ->isMultiple(),
        '#required' => $this->fieldDefinition->isRequired(),
        '#title' => $title,
        '#description' => $description,
        '#max_delta' => $max,
      );
      // Add 'add more' button, if not working with a programmed form.
      if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED && !$form_state->isProgrammed()) {
        $id_prefix = implode('-', array_merge($parents, array($field_name)));
        $wrapper_id = Html::getUniqueId($id_prefix . '-add-more-wrapper');
        $elements['#prefix'] = '<div id="' . $wrapper_id . '">';
        $elements['#suffix'] = '</div>';

        $elements['add_more'] = array(
          '#type' => 'submit',
          '#name' => strtr($id_prefix, '-', '_') . '_add_more',
          '#value' => t('Add another item'),
          '#attributes' => array('class' => array('field-add-more-submit')),
          '#limit_validation_errors' => array(array_merge($parents, array($field_name))),
          '#submit' => array(array(get_class($this), 'addMoreSubmit')),
          '#ajax' => array(
            'callback' => array(get_class($this), 'addMoreAjax'),
            'wrapper' => $wrapper_id,
            'effect' => 'fade',
          ),
        );
      }
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_settings = $this->getFieldSettings();

    // The field settings include defaults for the field type. However, this
    // widget is a base class for other widgets (e.g., ImageWidget) that may act
    // on field types without these expected settings.
    $field_settings += array(
      'display_default' => NULL,
      'display_field' => NULL,
      'description_field' => NULL,
    );

    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()
      ->getCardinality();
    $field_name = $this->fieldDefinition->getName();
    $defaults = array(
      'fids' => array(),
      'display' => (bool) $field_settings['display_default'],
      'description' => '',
    );

    // Essentially we use the managed_file type, extended with some
    // enhancements.
    $element_info = $this->elementInfo->getInfo('managed_file');
    $element['#type'] = 'elements_need_a_type';
    $element['#theme_wrappers'] = array('form_element');
    $element['file-wrap']['#type'] = 'container';
    $element['file-wrap']['select'] = [
      '#type' => 'radios',
      '#options' => [
        'file' => $this->t('File'),
        'remote-file' => $this->t('Remote file'),
      ],
    ];
    $element['file-wrap']['remote-file'] = [
      '#type' => 'url',
      '#title' => $this->t('Remote file'),
      '#states' => array(
        // Only show this field when the 'remote file' radio is selected.
        'visible' => array(
          ':input[name="' . $field_name . '[' . $delta . '][file-wrap][select]"]' => array('value' => 'remote-file'),
        ),
        'enabled' => array(
          ':input[name="' . $field_name . '[' . $delta . '][file-wrap][select]"]' => array('value' => 'remote-file'),
        ),
      ),
    ];
    $element['#cardinality'] = $cardinality;
    $element['file-wrap']['file'] = array(
      '#type' => 'managed_file',
      '#title_display' => 'invisible',
      '#title' => $this->fieldDefinition->getLabel(),
      '#upload_location' => $items[$delta]->getUploadLocation(),
      '#upload_validators' => $items[$delta]->getUploadValidators(),
      '#value_callback' => array(get_class($this), 'value'),
      '#process' => array_merge($element_info['#process'], array(
        array(
          get_class($this),
          'process',
        ),
      )),
      '#progress_indicator' => $this->getSetting('progress_indicator'),
      // Allows this field to return an array instead of a single value.
      '#extended' => TRUE,
      // Add properties needed by value() and process() methods.
      '#field_name' => $this->fieldDefinition->getName(),
      '#entity_type' => $items->getEntity()->getEntityTypeId(),
      '#display_field' => (bool) $field_settings['display_field'],
      '#display_default' => $field_settings['display_default'],
      '#description_field' => $field_settings['description_field'],
      '#cardinality' => $cardinality,
      '#states' => array(
        // Only show this field when the 'file' radio is selected.
        'visible' => array(
          ':input[name="' . $field_name . '[' . $delta . '][file-wrap][select]"]' => array('value' => 'file'),
        ),
        'enabled' => array(
          ':input[name="' . $field_name . '[' . $delta . '][file-wrap][select]"]' => array('value' => 'file'),
        ),
      ),
    );

    $element['#weight'] = $delta;
    $element['file-wrap']['file']['#default_value'] = $defaults;
    // Field stores FID value in a single mode, so we need to transform it for
    // form element to recognize it correctly.
    if (!isset($items[$delta]->fids) && isset($items[$delta]->target_id)) {
      /** @var \Drupal\rdf_file\RdfFileHandler $file_handler */
      $file_handler = \Drupal::service('rdf_file.handler');
      $target_id = $items[$delta]->target_id;
      $file = $file_handler::urlToFile($target_id);
      if ($file) {
        $items[$delta]->fids = [$file->id()];
        if ($file instanceof RemoteFile) {
          $element['file-wrap']['remote-file']['#default_value'] = $target_id;
          $element['file-wrap']['select']['#default_value'] = 'remote-file';
          $element['file-wrap']['file']['#default_value'] = NULL;
        }
        else {
          $element['file-wrap']['file']['#default_value'] = $items[$delta]->getValue() + $defaults;
          $element['file-wrap']['select']['#default_value'] = 'file';
          $element['file-wrap']['remote-file']['#default_value'] = NULL;
        }
      }
    }

    $default_fids = $element['file-wrap']['file']['#default_value']['fids'];
    if (empty($default_fids)) {
      $file_upload_help = array(
        '#theme' => 'file_upload_help',
        '#description' => $element['#description'],
        '#upload_validators' => $element['file-wrap']['file']['#upload_validators'],
        '#cardinality' => $cardinality,
      );
      $element['#description'] = \Drupal::service('renderer')
        ->renderPlain($file_upload_help);
      $element['#multiple'] = $cardinality != 1 ? TRUE : FALSE;
      if ($cardinality != 1 && $cardinality != -1) {
        $element['#element_validate'] = array(
          array(
            get_class($this),
            'validateMultipleCount',
          ),
        );
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Since file upload widget now supports uploads of more than one file at a
    // time it always returns an array of fids. We have to translate this to a
    // single fid, as field expects single value.
    /** @var \Drupal\rdf_file\RdfFileHandler $file_handler */
    $file_handler = \Drupal::service('rdf_file.handler');
    $new_values = array();
    foreach ($values as $delta => &$value) {
      // Skip when element is deleted.
      $trigger = $form_state->getTriggeringElement();
      $trigger_elem = array_pop($trigger['#array_parents']);
      // Skip the element where the delete button has been pressed.
      if ($trigger_elem == 'remove_button') {
        $trigger_delta = $trigger['#array_parents'][2];
        if ($trigger_delta == $delta) {
          continue;
        }
      }
      $type = $value['file-wrap']['select'];
      // Local file.
      if ($type == 'file') {
        foreach ($value['file-wrap']['file']['fids'] as $fid) {
          $new_value = $value;
          $file = File::load($fid);
          $new_value['target_id'] = $file_handler->fileToUrl($file);
          unset($new_value['fids']);
          $new_values[] = $new_value;
        }
      }
      // Remote file.
      elseif ($type == 'remote-file') {
        $new_value = $value;
        if (!empty($value['file-wrap']['remote-file'])) {
          $new_value['target_id'] = $value['file-wrap']['remote-file'];
          unset($new_value['fids']);
          $new_values[] = $new_value;
        }
      }
    }

    return $new_values;
  }

  /**
   * {@inheritdoc}
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    $item = $element['#value'];
    $item['fids'] = $element['fids']['#value'];

    // Add the display field if enabled.
    if ($element['#display_field']) {
      $element['display'] = array(
        '#type' => empty($item['fids']) ? 'hidden' : 'checkbox',
        '#title' => t('Include file in display'),
        '#attributes' => array('class' => array('file-display')),
      );
      if (isset($item['display'])) {
        $element['display']['#value'] = $item['display'] ? '1' : '';
      }
      else {
        $element['display']['#value'] = $element['#display_default'];
      }
    }
    else {
      $element['display'] = array(
        '#type' => 'hidden',
        '#value' => '1',
      );
    }

    // Add the description field if enabled.
    if ($element['#description_field'] && $item['fids']) {
      $config = \Drupal::config('file.settings');
      $element['description'] = array(
        '#type' => $config->get('description.type'),
        '#title' => t('Description'),
        '#value' => isset($item['description']) ? $item['description'] : '',
        '#maxlength' => $config->get('description.length'),
        '#description' => t('The description may be used as the label of the link to the file.'),
      );
    }

    // Adjust the Ajax settings so that on upload and remove of any individual
    // file, the entire group of file fields is updated together.
    if ($element['#cardinality'] != 1) {
      $parents = array_slice($element['#array_parents'], 0, -3);
      $new_options = array(
        'query' => array(
          'element_parents' => implode('/', $parents),
        ),
      );
      $field_element = NestedArray::getValue($form, $parents);
      $new_wrapper = $field_element['#id'] . '-ajax-wrapper';
      foreach (Element::children($element) as $key) {
        if (isset($element[$key]['#ajax'])) {
          $element[$key]['#ajax']['options'] = $new_options;
          $element[$key]['#ajax']['wrapper'] = $new_wrapper;
        }
      }
      unset($element['#prefix'], $element['#suffix']);
    }

    // Add another submit handler to the upload and remove buttons, to implement
    // functionality needed by the field widget. This submit handler, along with
    // the rebuild logic in file_field_widget_form() requires the entire field,
    // not just the individual item, to be valid.
    foreach (array('upload_button', 'remove_button') as $key) {
      $element[$key]['#submit'][] = array(get_called_class(), 'submit');
      $parent = array(array_slice($element['#parents'], 0, -1));
      $element[$key]['#limit_validation_errors'] = $parent;
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function submit($form, FormStateInterface $form_state) {
    // During the form rebuild, formElement() will create field item widget
    // elements using re-indexed deltas, so clear out FormState::$input to
    // avoid a mismatch between old and new deltas. The rebuilt elements will
    // have #default_value set appropriately for the current state of the field,
    // so nothing is lost in doing this.
    $button = $form_state->getTriggeringElement();
    $parents = array_slice($button['#parents'], 0, -4);
    NestedArray::setValue($form_state->getUserInput(), $parents, NULL);

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -3));
    $field_name = $element['#field_name'];
    $parents = $element['#field_parents'];

    $submitted_values = NestedArray::getValue($form_state->getValues(), array_slice($button['#parents'], 0, -3));
    foreach ($submitted_values as $delta => $submitted_value) {
      if (empty($submitted_value['file-wrap']['file']['fids'])) {
        unset($submitted_values[$delta]);
      }
    }

    // If there are more files uploaded via the same widget, we have to separate
    // them, as we display each file in it's own widget.
    $new_values = array();
    foreach ($submitted_values as $delta => $submitted_value) {
      if (is_array($submitted_value['file-wrap']['file']['fids'])) {
        foreach ($submitted_value['file-wrap']['file']['fids'] as $fid) {
          $new_value = $submitted_value;
          $new_value['file-wrap']['file']['fids'] = array($fid);
          $new_values[] = $new_value;
        }
      }
      else {
        $new_value = $submitted_value;
      }
    }

    // Re-index deltas after removing empty items.
    $submitted_values = array_values($new_values);

    // Update form_state values.
    NestedArray::setValue($form_state->getValues(), array_slice($button['#parents'], 0, -4), $submitted_values);

    // Update items.
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    $field_state['items'] = $submitted_values;
    static::setWidgetState($parents, $field_name, $form_state, $field_state);
  }

}
