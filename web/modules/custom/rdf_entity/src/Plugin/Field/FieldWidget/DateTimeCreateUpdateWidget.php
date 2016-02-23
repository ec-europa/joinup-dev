<?php
/**
 * @file
 * Contains \Drupal\datetime\Plugin\Field\FieldWidget\DateTimeDefaultWidget.
 */

namespace Drupal\rdf_entity\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\Field\FieldWidget\DateTimeWidgetBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Widget to turn a date field into a 'created date' or 'updated date' field.
 *
 * @FieldWidget(
 *   id = "datetime_create_update",
 *   label = @Translation("Created or updated"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 */
class DateTimeCreateUpdateWidget extends DateTimeWidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The date format storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $dateStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityStorageInterface $date_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->dateStorage = $date_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity.manager')->getStorage('date_format')
    );
  }
  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'behaviour' => 'create',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['behaviour'] = array(
      '#type' => 'select',
      '#options' => [
        'create' => t('Create'),
        'modified' => t('Modified'),
      ],
      '#title' => $this->t('Behave like the created or modified field'),
      '#default_value' => $this->getSetting('behaviour'),
      '#required' => TRUE,
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $summary[] = $this->t('Behave like a @type field.', array('@type' => $this->getSetting('behaviour')));
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Determine if this is an existing or a new entity.
    /** @var \Drupal\Core\Entity\ContentEntityFormInterface $form_object */
    $form_object = $form_state->getFormObject();
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $form_object->getEntity();
    $is_new = empty($entity->id());

    $behaviour = $this->getSetting('behaviour');
    if (($behaviour == 'create' && $is_new) || $behaviour == 'modified') {
      $element['value'] = [
        '#type' => 'value',
        '#value' => date('c'),
      ];
    }
    return $element;
  }

}
