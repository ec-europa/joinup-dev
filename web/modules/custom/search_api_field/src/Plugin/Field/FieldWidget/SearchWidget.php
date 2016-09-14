<?php

namespace Drupal\search_api_field\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\facets\Entity\Facet;
use Drupal\search_api_field\Plugin\Field\FieldType\SearchItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'link' widget.
 *
 * @FieldWidget(
 *   id = "search_default",
 *   label = @Translation("Search widget"),
 *   field_types = {
 *     "search"
 *   }
 * )
 */
class SearchWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a SearchWidget object.
   *
   * @param array $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getTableHeader() {
    return array(
      $this->t('Field'),
      $this->t('Weight'),
      $this->t('Parent'),
      $this->t('Label'),
      array('data' => $this->t('Format'), 'colspan' => 3),
    );
  }

  /**
   * Get defined facet regions.
   *
   * @return array
   *    List of facet regions.
   */
  protected function getRegions() {
    $storage = $this->fieldDefinition->getFieldStorageDefinition();
    $facet_regions = $storage->getSetting('facet_regions');
    $regions = [];
    foreach ($facet_regions as $id => $label) {
      $regions[$id] = [
        'title' => $label,
        'message' => $this->t('No field is displayed.'),
      ];
    }
    $regions['hidden'] = [
      'title' => $this->t('Disabled', array(), array('context' => 'Plural')),
      'message' => $this->t('No field is hidden.'),
    ];
    return $regions;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\link\LinkItemInterface $item */
    $item = $items[$delta];
    $facets = $this->getFacets();

    $element += [
      '#extra' => array_keys($facets),
    ];

    $table = [
      '#type' => 'field_ui_table',
      '#header' => $this->getTableHeader(),
      '#regions' => $this->getRegions(),
      '#attributes' => [
        'class' => ['field-ui-overview'],
        'id' => 'field-display-overview',
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'field-weight',
        ],
        [
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'field-parent',
          'subgroup' => 'field-parent',
          'source' => 'field-name',
        ],
      ],
    ];

    $form['#attached']['library'][] = 'search_api_field/drupal.search_api_field';
    foreach ($facets as $facet_id => $facet) {
      $table[$facet_id] = $this->buildFacetRow($facet, $item);
    }
    $element['fields'] = $table;
    $element['refresh_rows'] = ['#type' => 'hidden'];
    $element['refresh'] = [
      '#type' => 'submit',
      '#value' => $this->t('Refresh'),
      '#op' => 'refresh_table',
      '#submit' => ['::multistepSubmit'],
      '#ajax' => [
        'callback' => '::multistepAjax',
        'wrapper' => 'field-display-overview-wrapper',
        'effect' => 'fade',
        // The button stays hidden, so we hide the Ajax spinner too. Ad-hoc
        // spinners will be added manually by the client-side script.
        'progress' => 'none',
      ],
      '#attributes' => [
        'class' => [
          'visually-hidden',
          'row-refresher',
        ],
      ],
    ];

    $default_values = $item->get('value')->getValue();
    $element['query_presets'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Query presets'),
      '#description' => $this->t('Presets to apply to the query when it is executed. Must be entered in LUCENE syntax.'),
      '#default_value' => isset($default_values['query_presets']) ? $default_values['query_presets'] : '',
    ];

    $element['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Limit'),
      '#description' => $this->t('The number of results to show per page.'),
      '#default_value' => isset($default_values['limit']) ? $default_values['limit'] : 10,
      '#min' => 1,
    ];

    return $element;
  }

  /**
   * Returns the region to which a row belongs.
   *
   * @param array $row
   *   The row element.
   *
   * @return string|null
   *   The region name this row belongs to.
   */
  public function getRowRegion($row) {
    return $row['plugin']['type']['#value'];
  }

  /**
   * Form submission handler for multistep buttons.
   */
  public function multistepSubmit($form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $op = $trigger['#op'];

    switch ($op) {
      case 'edit':
        // Store the field whose settings are currently being edited.
        $field_name = $trigger['#field_name'];
        $form_state->set('plugin_settings_edit', $field_name);
        break;

      case 'update':
        // Set the field back to 'non edit' mode, and update $this->entity with
        // the new settings fro the next rebuild.
        $field_name = $trigger['#field_name'];
        $form_state->set('plugin_settings_edit', NULL);
        $form_state->set('plugin_settings_update', $field_name);
        $this->entity = $this->buildEntity($form, $form_state);
        break;

      case 'cancel':
        // Set the field back to 'non edit' mode.
        $form_state->set('plugin_settings_edit', NULL);
        break;

      case 'refresh_table':
        // If the currently edited field is one of the rows to be refreshed, set
        // it back to 'non edit' mode.
        $updated_rows = explode(' ', $form_state->getValue('refresh_rows'));
        $plugin_settings_edit = $form_state->get('plugin_settings_edit');
        if ($plugin_settings_edit && in_array($plugin_settings_edit, $updated_rows)) {
          $form_state->set('plugin_settings_edit', NULL);
        }
        break;
    }
    $form_state->setRebuild();
  }

  /**
   * Ajax handler for multistep buttons.
   */
  public function multistepAjax($form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $op = $trigger['#op'];

    // Pick the elements that need to receive the ajax-new-content effect.
    $updated_rows = $updated_columns = [];
    switch ($op) {
      case 'edit':
        $updated_rows = array($trigger['#field_name']);
        $updated_columns = array('plugin');
        break;

      case 'update':
      case 'cancel':
        $updated_rows = array($trigger['#field_name']);
        $updated_columns = array('plugin', 'settings_summary', 'settings_edit');
        break;

      case 'refresh_table':
        $updated_rows = array_values(explode(' ', $form_state->getValue('refresh_rows')));
        $updated_columns = array('settings_summary', 'settings_edit');
        break;
    }

    foreach ($updated_rows as $name) {
      foreach ($updated_columns as $key) {
        $element = &$form['fields'][$name][$key];
        $element['#prefix'] = '<div class="ajax-new-content">' . (isset($element['#prefix']) ? $element['#prefix'] : '');
        $element['#suffix'] = (isset($element['#suffix']) ? $element['#suffix'] : '') . '</div>';
      }
    }

    // Return the whole table.
    return $form['fields'];
  }

  /**
   * Build draggable facet row.
   *
   * @param \Drupal\facets\Entity\Facet $facet
   *    Facet.
   * @param \Drupal\search_api_field\Plugin\Field\FieldType\SearchItem $item
   *    The search field item.
   *
   * @return array
   *    Render array of the row.
   */
  protected function buildFacetRow(Facet $facet, SearchItem $item) {
    $value = $item->get('value')->getValue();
    $areas = !empty($value['fields']) ? $value['fields'] : [];
    $facet_config = NULL;
    if ($areas) {
      foreach ($areas as $facet_name => $facet_data) {
        if ($facet_name == $facet->id()) {
          $facet_config = $facet_data;
        }
      }
    }
    $display_options = NULL;
    $regions = array_keys($this->getRegions());
    $extra_field_row = array(
      '#attributes' => array('class' => array('draggable', 'tabledrag-leaf')),
      '#row_type' => 'extra_field',
      '#region_callback' => array($this, 'getRowRegion'),
      '#js_settings' => array('rowHandler' => 'field'),
      'human_name' => array(
        '#markup' => $facet->getName(),
      ),
      'weight' => array(
        '#type' => 'textfield',
        '#title' => $this->t('Weight for @title', array('@title' => $facet->getName())),
        '#title_display' => 'invisible',
        '#default_value' => $display_options ? $display_options['weight'] : 0,
        '#size' => 3,
        '#attributes' => array('class' => array('field-weight')),
      ),
      'parent_wrapper' => array(
        'parent' => array(
          '#type' => 'select',
          '#title' => $this->t('Parents for @title', array('@title' => $facet->getName())),
          '#title_display' => 'invisible',
          '#options' => array_combine($regions, $regions),
          '#empty_value' => '',
          '#attributes' => array(
            'class' => array(
              'js-field-parent',
              'field-parent',
            ),
          ),
        ),
        'hidden_name' => array(
          '#type' => 'hidden',
          '#default_value' => $facet->id(),
          '#attributes' => array('class' => array('field-name')),
        ),
      ),
      'plugin' => array(
        'type' => array(
          '#type' => 'select',
          '#title' => $this->t('Visibility for @title', array('@title' => $facet->getName())),
          '#title_display' => 'invisible',
          '#options' => array_combine($regions, $regions),
          '#default_value' => !empty($facet_config) ? $facet_config['region'] : 'hidden',
          '#attributes' => array('class' => array('field-plugin-type')),
        ),
      ),
      'settings_summary' => array(),
      'settings_edit' => array(),
    );

    return $extra_field_row;
  }

  /**
   * Get a list of applicable facets.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *    List of facets.
   */
  protected function getFacets() {
    $storage = $this->fieldDefinition->getFieldStorageDefinition();
    $facet_storage = $this->entityTypeManager->getStorage('facets_facet');
    $facets = $facet_storage->loadByProperties(['facet_source_id' => 'search_api_field:' . $storage->id()]);
    return $facets;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    if (empty($values)) {
      return $values;
    }

    // Clean the values, skipping submitted button values and placing everything
    // under a 'value' array element which will be serialized.
    $cleaned_values = [];
    foreach ($values as $delta => $value) {
      if (!empty($value['fields'])) {
        foreach ($value['fields'] as $fn => $field) {
          $cleaned_values[$delta]['value']['fields'][$fn] = [
            'weight' => $field['weight'],
            'region' => $field['plugin']['type'],
          ];
        }
      }

      $cleaned_values[$delta]['value']['query_presets'] = $values[$delta]['query_presets'];
      $cleaned_values[$delta]['value']['limit'] = $values[$delta]['limit'];
    }
    return $cleaned_values;
  }

}
