<?php

namespace Drupal\search_api_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Url;
use Drupal\link\LinkItemInterface;

/**
 * Plugin implementation of the 'link' field type.
 *
 * @FieldType(
 *   id = "search",
 *   label = @Translation("Search"),
 *   description = @Translation("Stores the search settings related to this field."),
 *   default_widget = "search_default",
 *   default_formatter = "search",
 * )
 */
class SearchItem extends FieldItemBase implements LinkItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return array(
      'index' => NULL,
      'facet_regions' => [],
    ) + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('any')
      ->setLabel(t('Data'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'blob',
          'size' => 'big',
          'serialize' => TRUE,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = array();
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $index_options = array();
    // @FIXME Inject properly.
    $search_api_indexes = \Drupal::entityTypeManager()->getStorage('search_api_index')->loadMultiple();
    /* @var  $search_api_index \Drupal\search_api\IndexInterface */
    foreach ($search_api_indexes as $search_api_index) {
      $index_options[$search_api_index->id()] = $search_api_index->label();
    }
    $element['index'] = array(
      '#type' => 'select',
      '#title' => $this->t('Search API index'),
      '#options' => $index_options,
      '#default_value' => $this->getSetting('index'),
      '#required' => TRUE,
    );

    $facet_regions = $this->getSetting('facet_regions');
    $facet_regions_function = $this->getSetting('facet_regions_function');

    $element['facet_regions'] = array(
      '#type' => 'textarea',
      '#title' => t('Facet regions'),
      '#default_value' => $this->allowedValuesString($facet_regions),
      '#rows' => 10,
      '#access' => empty($facet_regions_function),
      '#element_validate' => array(array(get_class($this), 'validateAllowedValues')),
      '#field_has_data' => $has_data,
      '#field_name' => $this->getFieldDefinition()->getName(),
      '#entity_type' => $this->getEntity()->getEntityTypeId(),
      '#facet_regions' => $facet_regions,
    );

    $element['facet_regions']['#description'] = $this->allowedValuesDescription();

    $element['facet_regions_function'] = array(
      '#type' => 'item',
      '#title' => t('Allowed values list'),
      '#markup' => t('The value of this field is being determined by the %function function and may not be changed.', array('%function' => $facet_regions_function)),
      '#access' => !empty($facet_regions_function),
      '#value' => $facet_regions_function,
    );

    return $element;
  }

  /**
   * Callback: #element_validate for options field allowed values.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param FormStateInterface $form_state
   *   The current state of the form for the form this element belongs to.
   *
   * @see \Drupal\Core\Render\Element\FormElement::processPattern()
   */
  public static function validateAllowedValues($element, FormStateInterface $form_state) {
    $values = static::extractAllowedValues($element['#value'], $element['#field_has_data']);

    if (!is_array($values)) {
      $form_state->setError($element, t('Allowed values list: invalid input.'));
    }
    else {
      // Check that keys are valid for the field type.
      foreach ($values as $key => $value) {
        if ($error = static::validateAllowedValue($key)) {
          $form_state->setError($element, $error);
          break;
        }
      }

      // Prevent removing values currently in use.
      if ($element['#field_has_data']) {
        $lost_keys = array_keys(array_diff_key($element['#allowed_values'], $values));
        if (_options_values_in_use($element['#entity_type'], $element['#field_name'], $lost_keys)) {
          $form_state->setError($element, t('Allowed values list: some values are being removed while currently in use.'));
        }
      }

      $form_state->setValueForElement($element, $values);
    }
  }

  /**
   * Extracts the allowed values array from the allowed_values element.
   *
   * @param string $string
   *   The raw string to extract values from.
   * @param bool $has_data
   *   The current field already has data inserted or not.
   *
   * @return array|null
   *   The array of extracted key/value pairs, or NULL if the string is invalid.
   *
   * @see \Drupal\options\Plugin\Field\FieldType\ListTextItem::allowedValuesString()
   */
  protected static function extractAllowedValues($string, $has_data) {
    $values = array();

    $list = explode("\n", $string);
    $list = array_map('trim', $list);
    $list = array_filter($list, 'strlen');

    $generated_keys = $explicit_keys = FALSE;
    foreach ($list as $position => $text) {
      // Check for an explicit key.
      $matches = array();
      if (preg_match('/(.*)\|(.*)/', $text, $matches)) {
        // Trim key and value to avoid unwanted spaces issues.
        $key = trim($matches[1]);
        $value = trim($matches[2]);
        $explicit_keys = TRUE;
      }
      // Otherwise see if we can use the value as the key.
      elseif (!static::validateAllowedValue($text)) {
        $key = $value = $text;
        $explicit_keys = TRUE;
      }
      // Otherwise see if we can generate a key from the position.
      elseif (!$has_data) {
        $key = (string) $position;
        $value = $text;
        $generated_keys = TRUE;
      }
      else {
        return NULL;
      }

      $values[$key] = $value;
    }

    // We generate keys only if the list contains no explicit key at all.
    if ($explicit_keys && $generated_keys) {
      return NULL;
    }

    return $values;
  }

  /**
   * Checks whether a candidate allowed value is valid.
   *
   * @param string $option
   *   The option value entered by the user.
   */
  protected static function validateAllowedValue($option) {

  }

  /**
   * Generates a string representation of an array of 'allowed values'.
   *
   * This string format is suitable for edition in a textarea.
   *
   * @param array $values
   *   An array of values, where array keys are values and array values are
   *   labels.
   *
   * @return string
   *   The string representation of the $values array:
   *    - Values are separated by a carriage return.
   *    - Each value is in the format "value|label" or "value".
   */
  protected function allowedValuesString($values) {
    $lines = array();
    foreach ($values as $key => $value) {
      $lines[] = "$key|$value";
    }
    return implode("\n", $lines);
  }

  /**
   * Description for the allowed values.
   *
   * @return string
   *    Field description.
   */
  protected function allowedValuesDescription() {
    $description = '<p>' . t('The possible values this field can contain. Enter one value per line, in the format key|label.');
    $description .= '<br/>' . t('The key is the stored value, and must be numeric. The label will be used in displayed values and edit forms.');
    $description .= '<br/>' . t('The label is optional: if a line contains a single number, it will be used as key and label.');
    $description .= '<br/>' . t('Lists of labels are also accepted (one label per line), only if the field does not hold any values yet. Numeric keys will be automatically generated from the positions in the list.');
    $description .= '</p>';
    $description .= '<p>' . t('Allowed HTML tags in labels: @tags', array('@tags' => FieldFilteredMarkup::displayAllowedTags())) . '</p>';
    return $description;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public function isExternal() {
    return $this->getUrl()->isExternal();
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'value';
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    return Url::fromUri($this->uri);
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    // Treat the values as property value of the main property, if no array is
    // given.
    if (isset($values) && !is_array($values)) {
      $values = [static::mainPropertyName() => $values];
    }
    if (isset($values)) {
      $values += [
        'options' => [],
      ];
    }
    // Unserialize the values.
    // @todo The storage controller should take care of this, see
    //   SqlContentEntityStorage::loadFieldItems, see
    //   https://www.drupal.org/node/2414835
    if (is_string($values['options'])) {
      $values['options'] = unserialize($values['options']);
    }
    parent::setValue($values, $notify);
  }

}
