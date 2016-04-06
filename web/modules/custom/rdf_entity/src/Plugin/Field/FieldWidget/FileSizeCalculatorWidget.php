<?php

namespace Drupal\rdf_entity\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'file_size_calculator_widget' widget.
 *
 * @FieldWidget(
 *   id = "file_size_calculator_widget",
 *   label = @Translation("Automatic file size calculator"),
 *   field_types = {
 *     "float"
 *   }
 * )
 */
class FileSizeCalculatorWidget extends WidgetBase implements ContainerFactoryPluginInterface {
  protected $targetEntity;
  protected $targetBundle;
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings,
                              EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->entityFieldManager = $entity_field_manager;
    $this->targetEntity = $field_definition->getTargetEntityTypeId();
    $this->targetBundle = $field_definition->getTargetBundle();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['third_party_settings'], $container->get('entity_field.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'file_fields' => [],
      'size_type' => '1024',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['file_fields'] = array(
      '#type' => 'select',
      '#title' => t('File fields'),
      '#options' => $this->getAvailableFields(),
      '#default_value' => $this->getSetting('file_fields'),
      '#multiple' => TRUE,
      '#required' => TRUE,
    );

    $elements['size_type'] = array(
      '#type' => 'select',
      '#title' => t('File size type'),
      '#options' => $this->getSizeOptions(),
      '#default_value' => $this->getSetting('size_type'),
      '#required' => TRUE,
      '#multiple' => FALSE,
    );

    return $elements;
  }

  /**
   * Gets a list of the file fields of the entity this field is attached to.
   *
   * @return array
   *   An array of fields keyed by their id.
   */
  public function getAvailableFields() {
    $available_fields = [];
    /** @var FieldDefinitionInterface $field_definition */
    foreach ($this->entityFieldManager->getFieldDefinitions($this->targetEntity, $this->targetBundle) as $field_key => $field_definition) {
      if ($field_definition->getType() == 'file') {
        $available_fields[$field_key] = $field_definition->label();
      }
    }
    return $available_fields;
  }

  /**
   * Returns the options for the size format.
   *
   * @return array
   *   An array of options keyed by their number of bytes.
   */
  public function getSizeOptions() {
    return [
      '1' => t('Bytes'),
      '1024' => t('Kilo Bytes'),
      '1048576' => t('Mega Bytes'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = t('File fields to count: :filefields',
      [':filefields' => implode(', ', $this->getSetting('file_fields'))]);
    $summary[] = t('Size format: :format',
      [':format' => $this->getSizeOptions()[$this->getSetting('size_type')]]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    if (empty($this->getSetting('file_fields'))) {
      drupal_set_message(t("No file fields are selected for the :id auto calculated field.", [':id' => $this->fieldDefinition->id()]), 'warning');
    }

    $element = [];
    $element['value'] = $element + array(
      '#type' => 'value',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $countable_fields = $this->getSetting('file_fields');
    $file_size = 0;
    foreach ($countable_fields as $field) {
      if ($field_value = $form_state->getValue($field)) {
        $files_values = array_filter(array_column($field_value, 'fids'));
        foreach ($files_values as $file_value) {
          /** @var FileInterface $file */
          $file = File::load(reset($file_value));
          if ($file) {
            $file_size += $file->getSize();
          }
        }
      }
    }
    $format = intval($this->getSetting('size_type'));
    $values = $file_size / $format;
    return $values;
  }

}
