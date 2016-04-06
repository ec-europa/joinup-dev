<?php

namespace Drupal\rdf_entity\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\Entity\File;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'url_file_synchronizer_widget' widget.
 *
 * @FieldWidget(
 *   id = "url_file_synchronizer_widget",
 *   label = @Translation("Url file synchronizer widget"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class UrlFileSynchronizerWidget extends LinkWidget implements ContainerFactoryPluginInterface {
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
      '#required' => FALSE,
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
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = t('File fields to mirror: :filefields',
      [':filefields' => implode(', ', $this->getSetting('file_fields'))]);

    return $summary + parent::settingsSummary();
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    global $base_url;
    $values = parent::massageFormValues($values, $form, $form_state);

    $file_urls = [];
    $countable_fields = $this->getSetting('file_fields');
    foreach ($countable_fields as $field) {
      $files_values = array_filter(array_column($form_state->getValue($field), 'fids'));
      foreach ($files_values as $file_value) {
        /** @var FileInterface $file */
        $file = File::load(reset($file_value));
        if ($file) {
          $file_urls[] = $file->url();
        }
      }
    }

    // Remove removed files from access urls.
    foreach ($values as $delta => $value) {
      if (UrlHelper::isExternal($value['uri']) && UrlHelper::externalIsLocal($value['uri'], $base_url) && !in_array($value['uri'], $file_urls)
      ) {
        unset($values[$delta]);
      }
    }

    // Add new or updated files to the access urls.
    foreach ($file_urls as $file_url) {
      if (!array_search($file_url, array_column($values, 'uri'))) {
        $values[]['uri'] = $file_url;
      }
    }

    return $values;
  }

}
