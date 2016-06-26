<?php

namespace Drupal\search_api_field\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
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
 *   constraints = {"LinkType" = {}, "LinkAccess" = {}, "LinkExternalProtocols" = {}, "LinkNotExistingInternal" = {}}
 * )
 */
class SearchItem extends FieldItemBase implements LinkItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return array(
      'index' => NULL,
    ) + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['uri'] = DataDefinition::create('uri')
      ->setLabel(t('URI'));

    $properties['title'] = DataDefinition::create('string')
      ->setLabel(t('Link text'));

    $properties['options'] = MapDataDefinition::create()
      ->setLabel(t('Options'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'uri' => array(
          'description' => 'The URI of the link.',
          'type' => 'varchar',
          'length' => 2048,
        ),
        'title' => array(
          'description' => 'The link text.',
          'type' => 'varchar',
          'length' => 255,
        ),
        'options' => array(
          'description' => 'Serialized array of options for the link.',
          'type' => 'blob',
          'size' => 'big',
          'serialize' => TRUE,
        ),
      ),
      'indexes' => array(
        'uri' => array(array('uri', 30)),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = array();
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

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    if ($field_definition->getItemDefinition()->getSetting('link_type') & LinkItemInterface::LINK_EXTERNAL) {
      // Set of possible top-level domains.
      $tlds = array('com', 'net', 'gov', 'org', 'edu', 'biz', 'info');
      // Set random length for the domain name.
      $domain_length = mt_rand(7, 15);

      switch ($field_definition->getSetting('title')) {
        case DRUPAL_DISABLED:
          $values['title'] = '';
          break;
        case DRUPAL_REQUIRED:
          $values['title'] = $random->sentences(4);
          break;
        case DRUPAL_OPTIONAL:
          // In case of optional title, randomize its generation.
          $values['title'] = mt_rand(0, 1) ? $random->sentences(4) : '';
          break;
      }
      $values['uri'] = 'http://www.' . $random->word($domain_length) . '.' . $tlds[mt_rand(0, (sizeof($tlds) - 1))];
    }
    else {
      $values['uri'] = 'base:' . $random->name(mt_rand(1, 64));
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('uri')->getValue();
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
    return 'uri';
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
