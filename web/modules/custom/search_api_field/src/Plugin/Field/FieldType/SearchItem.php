<?php

namespace Drupal\search_api_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\search_api\Entity\Index as SearchApiIndex;

/**
 * Plugin implementation of the 'search_api_field' field type.
 *
 * @FieldType(
 *   id = "search_api_field",
 *   label = @Translation("Search API field"),
 *   description = @Translation("Stores the search settings related to this field."),
 *   default_widget = "search_api_field_default",
 *   default_formatter = "search_api_field",
 * )
 */
class SearchItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return array(
      'index' => NULL,
      'facet_regions' => [],
      'view_modes' => [],
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
  public static function mainPropertyName() {
    return 'value';
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
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $search_api_indexes = \Drupal::entityTypeManager()->getStorage('search_api_index')->loadMultiple();
    $index_options = [];
    /* @var  $search_api_index \Drupal\search_api\IndexInterface */
    foreach ($search_api_indexes as $search_api_index) {
      $index_options[$search_api_index->id()] = $search_api_index->label();
    }

    $element['index'] = [
      '#type' => 'select',
      '#title' => $this->t('Search API index'),
      '#options' => $index_options,
      '#default_value' => $this->getSetting('index'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [$this, 'buildAjaxForm'],
        'wrapper' => 'search-api-field-item-view-modes-wrapper',
      ],
    ];

    $facet_regions = $this->getSetting('facet_regions');
    $facet_regions_function = $this->getSetting('facet_regions_function');

    $element['facet_regions'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Facet regions'),
      '#description' => $this->allowedValuesDescription(),
      '#default_value' => $this->allowedValuesString($facet_regions),
      '#rows' => 10,
      '#access' => empty($facet_regions_function),
      '#element_validate' => [[get_class($this), 'validateAllowedValues']],
      '#field_has_data' => $has_data,
      '#field_name' => $this->getFieldDefinition()->getName(),
      '#entity_type' => $this->getEntity()->getEntityTypeId(),
      '#facet_regions' => $facet_regions,
      '#allowed_values' => $facet_regions,
    ];

    $element['facet_regions_function'] = [
      '#type' => 'item',
      '#title' => $this->t('Allowed values list'),
      '#markup' => $this->t('The value of this field is being determined by the %function function and may not be changed.', ['%function' => $facet_regions_function]),
      '#access' => !empty($facet_regions_function),
      '#value' => $facet_regions_function,
    ];

    // In order to allow ajax rendering of the view mode fieldset upon selecting
    // a search index, an additional wrapper element needs to contain the whole
    // view modes structure. Since the valid keys must be specified in the
    // self::defaultStorageSettings() method, the top parent has to hold the key
    // specified in there or the value will be lost before saving the config
    // entity. In the storageSettingsToConfigData() the data is normalized
    // back to remove the extra wrapping of the values.
    $element['view_modes'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'search-api-field-item-view-modes-wrapper',
      ],
    ];

    if ($form_state->isRebuilding()) {
      $index_id = $form_state->getValue(['settings', 'index']);
    }
    else {
      $index_id = $this->getSetting('index');
    }
    if (!empty($index_id)) {
      $element['view_modes']['wrapper'] = [
        '#type' => 'details',
        '#title' => $this->t('View modes'),
        '#open' => FALSE,
      ];

      $element['view_modes']['wrapper'] += $this->buildViewModesElements($index_id);
    }

    return $element;
  }

  /**
   * Builds the view modes form options for the selected index datasources.
   *
   * @param string $index_id
   *   The id of the search_api index.
   *
   * @return array
   *   The form definition for the view modes element.
   *
   * @see \Drupal\search_api\Plugin\views\row\SearchApiRow::buildOptionsForm()
   */
  protected function buildViewModesElements($index_id) {
    $element = [];
    $settings = $this->getSetting('view_modes');

    /* @var $search_api_index \Drupal\search_api\IndexInterface */
    $search_api_index = SearchApiIndex::load($index_id);

    foreach ($search_api_index->getDatasources() as $datasource_id => $datasource) {
      /** @var \Drupal\search_api\Plugin\search_api\datasource\ContentEntity $datasource_label */
      $datasource_label = $datasource->label();
      $bundles = $datasource->getBundles();
      if (!$datasource->getViewModes()) {
        $element[$datasource_id] = array(
          '#type' => 'item',
          '#title' => $this->t('Default View mode for datasource %name', array('%name' => $datasource_label)),
          '#description' => $this->t("This datasource doesn't have any view modes available. It is therefore not possible to display results of this datasource."),
        );
        continue;
      }

      foreach ($bundles as $bundle_id => $bundle_label) {
        $title = $this->t('View mode for datasource %datasource, bundle %bundle', array('%datasource' => $datasource_label, '%bundle' => $bundle_label));
        $view_modes = $datasource->getViewModes($bundle_id);
        if (!$view_modes) {
          $element[$datasource_id][$bundle_id] = array(
            '#type' => 'item',
            '#title' => $title,
            '#description' => $this->t("This bundle doesn't have any view modes available. It is therefore not possible to display results of this bundle using."),
          );
          continue;
        }
        $element[$datasource_id][$bundle_id] = array(
          '#type' => 'select',
          '#options' => $view_modes,
          '#title' => $title,
          '#default_value' => !empty($settings[$datasource_id][$bundle_id]) ? $settings[$datasource_id][$bundle_id] : key($view_modes),
        );
      }
    }

    return $element;
  }

  /**
   * Ajax callback to update view modes fieldset when the index field changes.
   */
  public function buildAjaxForm(array $form, FormStateInterface $form_state) {
    return $form['settings']['view_modes'];
  }

  /**
   * Callback: #element_validate for options field allowed values.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form for the form this element belongs to.
   *
   * @see \Drupal\options\Plugin\Field\FieldType\ListItemBase::validateAllowedValues()
   * @see \Drupal\Core\Render\Element\FormElement::processPattern()
   */
  public static function validateAllowedValues(array $element, FormStateInterface $form_state) {
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
   * @see \Drupal\options\Plugin\Field\FieldType\ListItemBase::extractAllowedValues()
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
   *
   * @see \Drupal\options\Plugin\Field\FieldType\ListItemBase::validateAllowedValue()
   */
  protected static function validateAllowedValue($option) {}

  /**
   * Generates a string representation of an array of 'facet_regions'.
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
   *
   * @see \Drupal\options\Plugin\Field\FieldType\ListItemBase::allowedValuesString()
   */
  protected function allowedValuesString(array $values) {
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
   *   Field description.
   */
  protected function allowedValuesDescription() {
    $description = '<p>' . t('The possible values this field can contain. Enter one value per line, in the format key|label.');
    $description .= '</p>';
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
  public static function storageSettingsToConfigData(array $settings) {
    // Remove the extra 'wrapper' element that was added to allow ajax rendering
    // of the view modes fieldset.
    // @see self::storageSettingsForm()
    if (array_key_exists('wrapper', $settings['view_modes'])) {
      $settings['view_modes'] = $settings['view_modes']['wrapper'];
    }

    return $settings;
  }

}
