<?php

namespace Drupal\piwik_download_count\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\field\Entity\FieldConfig;

/**
 * Plugin implementation of the 'download_count' field type.
 *
 * @FieldType(
 *   id = "download_count",
 *   label = @Translation("Download count"),
 *   description = @Translation("Fetches the # of downloads from Piwik."),
 *   default_widget = "download_count",
 *   default_formatter = "download_count"
 * )
 */
class DownloadCountFieldType extends FieldItemBase {
  public static function defaultFieldSettings() {
    return parent::defaultFieldSettings() + ['file_field' => NULL];
  }


  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Text value'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(TRUE);

    $properties['last_update'] = DataDefinition::create('datetime_iso8601')
      ->setLabel(t('Last updated'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'value' => [
          'type' => 'int',
          'unsigned' => TRUE,
          'size' => 'normal',
        ],
        'last_update' => [
          'description' => 'The time the value is synced back from Piwik.',
          'type' => 'varchar',
          'length' => 20,
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   *
   * blablabla
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    if ($max_length = $this->getSetting('max_length')) {
      $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
      $constraints[] = $constraint_manager->create('ComplexData', [
        'value' => [
          'Length' => [
            'max' => $max_length,
            'maxMessage' => t('%name: may not be longer than @max characters.', [
              '%name' => $this->getFieldDefinition()->getLabel(),
              '@max' => $max_length,
            ]),
          ],
        ],
      ]);
    }

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::fieldSettingsForm($form, $form_state);

    /** @var \Drupal\field_ui\Form\FieldConfigEditForm $field */
    $field_form = $form_state->getFormObject();
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $field_form->getEntity();
    $bundle = $entity->getTargetBundle();
    $entity_type = $entity->getTargetEntityTypeId();

    /** @var \Drupal\Core\Entity\EntityFieldManager $field_manager */
    $field_manager = \Drupal::getContainer()->get('entity_field.manager');

    $field_definitions = $field_manager->getFieldDefinitions($entity_type, $bundle);
    if (empty($field_definitions)) {
      return [];
    }
    $options = array_filter($field_definitions, function ($field_definition) {
      if (!$field_definition instanceof FieldConfig) {
        return FALSE;
      }
      return in_array($field_definition->get('field_type'), ['file', 'file_url']);
    });
    $defaults = array_map(function (FieldConfig $item) {
      return $item->label();
    }, $options);

    $form['file_field'] = [
      '#type' => 'select',
      '#title' => t('The source file field'),
      '#options' => $defaults,
      '#default_value' => $this->getSetting('file_field'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $values['value'] = $random->word(mt_rand(1, $field_definition->getSetting('max_length')));
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $elements = [];

    $elements['max_length'] = [
      '#type' => 'number',
      '#title' => t('Maximum length'),
      '#default_value' => $this->getSetting('max_length'),
      '#required' => TRUE,
      '#description' => t('The maximum length of the field in characters.'),
      '#min' => 1,
      '#disabled' => $has_data,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

}
