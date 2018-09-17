<?php

namespace Drupal\search_api_field\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\facets\Entity\Facet;
use Drupal\search_api_field\Plugin\Field\FieldType\SearchItem;
use Drupal\search_api_field\Plugin\FilterPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'search_api_field_default' widget.
 *
 * @FieldWidget(
 *   id = "search_api_field_default",
 *   label = @Translation("Search widget"),
 *   field_types = {
 *     "search_api_field"
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
   * The filter plugin manager service.
   *
   * @var \Drupal\search_api_field\Plugin\FilterPluginManagerInterface
   */
  protected $filterPluginManager;

  /**
   * Constructs a SearchWidget object.
   *
   * @param string $plugin_id
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
   * @param \Drupal\search_api_field\Plugin\FilterPluginManagerInterface $filter_plugin_manager
   *   The filter plugin manager service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, FilterPluginManagerInterface $filter_plugin_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->entityTypeManager = $entity_type_manager;
    $this->filterPluginManager = $filter_plugin_manager;
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
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.search_api_field.filter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'query_builder' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['query_builder'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable query builder.'),
      '#description' => $this->t('Shows the query builder interface in the form.'),
      '#default_value' => !empty($this->getSetting('query_builder')),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if (!empty($this->getSetting('query_builder'))) {
      $summary[] = $this->t('Query builder enabled.');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\search_api_field\Plugin\Field\FieldType\SearchItem $item */
    $item = $items[$delta];
    $default_values = $item->get('value')->getValue();
    $facets = $this->getFacets();

    $element['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable the search field'),
      '#description' => $this->t('Uncheck to disable completely the functionality.'),
      '#default_value' => $default_values['enabled'] ?? TRUE,
    ];

    // Construct a string that represents the name of the enabled field.
    $field_name = $this->fieldDefinition->getName();
    $enabled_field_path = "{$field_name}[{$delta}][enabled]";
    // Wrap all the remaining elements so they can be hidden when the above
    // checkbox is unchecked.
    $element['wrapper'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="' . $enabled_field_path . '"]' => ['checked' => TRUE],
        ],
      ],
    ];

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
    $element['wrapper']['fields'] = $table;
    $element['wrapper']['refresh_rows'] = ['#type' => 'hidden'];
    $element['wrapper']['refresh'] = [
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

    if (!empty($this->getSetting('query_builder'))) {
      $element['wrapper']['query_builder'] = $this->buildQueryBuilder($item, $delta, $form, $form_state);
    }

    $element['wrapper']['query_presets'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Query presets'),
      '#description' => $this->t('Presets to apply to the query when it is executed. Must be entered in LUCENE syntax.'),
      '#default_value' => isset($default_values['query_presets']) ? $default_values['query_presets'] : '',
    ];

    $element['wrapper']['limit'] = [
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
  public static function getRowRegion(array $row) {
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
        // the new settings from the next rebuild.
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
        $updated_rows = [$trigger['#field_name']];
        $updated_columns = ['plugin'];
        break;

      case 'update':
      case 'cancel':
        $updated_rows = [$trigger['#field_name']];
        $updated_columns = ['plugin', 'settings_summary', 'settings_edit'];
        break;

      case 'refresh_table':
        $updated_rows = array_values(explode(' ', $form_state->getValue('refresh_rows')));
        $updated_columns = ['settings_summary', 'settings_edit'];
        break;
    }

    foreach ($updated_rows as $name) {
      foreach ($updated_columns as $key) {
        $element = &$form['wrapper']['fields'][$name][$key];
        $element['#prefix'] = '<div class="ajax-new-content">' . (isset($element['#prefix']) ? $element['#prefix'] : '');
        $element['#suffix'] = (isset($element['#suffix']) ? $element['#suffix'] : '') . '</div>';
      }
    }

    // Return the whole table.
    return $form['fields'];
  }

  /**
   * Returns the headers for the facet table.
   */
  protected function getTableHeader() {
    return [
      $this->t('Field'),
      $this->t('Weight'),
      $this->t('Parent'),
      $this->t('Label'),
      ['data' => $this->t('Format'), 'colspan' => 3],
    ];
  }

  /**
   * Get defined facet regions.
   *
   * @return array
   *   List of facet regions.
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
      'title' => $this->t('Disabled', [], ['context' => 'Plural']),
      'message' => $this->t('No field is hidden.'),
    ];
    return $regions;
  }

  /**
   * Build draggable facet row.
   *
   * @param \Drupal\facets\Entity\Facet $facet
   *   Facet.
   * @param \Drupal\search_api_field\Plugin\Field\FieldType\SearchItem $item
   *   The search field item.
   *
   * @return array
   *   Render array of the row.
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
    $extra_field_row = [
      '#attributes' => ['class' => ['draggable', 'tabledrag-leaf']],
      '#row_type' => 'extra_field',
      '#region_callback' => [$this, 'getRowRegion'],
      '#js_settings' => ['rowHandler' => 'field'],
      'human_name' => [
        '#markup' => $facet->getName(),
      ],
      'weight' => [
        '#type' => 'textfield',
        '#title' => $this->t('Weight for @title', ['@title' => $facet->getName()]),
        '#title_display' => 'invisible',
        '#default_value' => $display_options ? $display_options['weight'] : 0,
        '#size' => 3,
        '#attributes' => ['class' => ['field-weight']],
      ],
      'parent_wrapper' => [
        'parent' => [
          '#type' => 'select',
          '#title' => $this->t('Parents for @title', ['@title' => $facet->getName()]),
          '#title_display' => 'invisible',
          '#options' => array_combine($regions, $regions),
          '#empty_value' => '',
          '#attributes' => [
            'class' => [
              'js-field-parent',
              'field-parent',
            ],
          ],
        ],
        'hidden_name' => [
          '#type' => 'hidden',
          '#default_value' => $facet->id(),
          '#attributes' => ['class' => ['field-name']],
        ],
      ],
      'plugin' => [
        'type' => [
          '#type' => 'select',
          '#title' => $this->t('Visibility for @title', ['@title' => $facet->getName()]),
          '#title_display' => 'invisible',
          '#options' => array_combine($regions, $regions),
          '#default_value' => !empty($facet_config) ? $facet_config['region'] : 'hidden',
          '#attributes' => ['class' => ['field-plugin-type']],
        ],
      ],
      'settings_summary' => [],
      'settings_edit' => [],
    ];

    return $extra_field_row;
  }

  /**
   * Get a list of applicable facets.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   List of facets.
   */
  protected function getFacets() {
    $storage = $this->fieldDefinition->getFieldStorageDefinition();
    $facet_storage = $this->entityTypeManager->getStorage('facets_facet');
    $facets = $facet_storage->loadByProperties(['facet_source_id' => 'search_api_field:' . $storage->id()]);
    return $facets;
  }

  /**
   * Builds the query builder form interface.
   *
   * @param \Drupal\search_api_field\Plugin\Field\FieldType\SearchItem $item
   *   The value for the specific delta of this field.
   * @param int $delta
   *   The order of this item in the array of sub-elements (0, 1, 2, etc.).
   * @param array $form
   *   The form structure where widgets are being attached to.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure for the query builder.
   */
  protected function buildQueryBuilder(SearchItem $item, int $delta, array &$form, FormStateInterface $form_state) {
    $index = $this->getSearchApiIndex();
    if (!$index) {
      return [];
    }

    // Wrap everything with a container for ajax rebuilds.
    $wrapper_id = Html::getUniqueId('query-builder-wrapper');
    $element = [
      '#type' => 'container',
      '#attributes' => [
        'id' => $wrapper_id,
      ],
    ];

    $parents = $form['#parents'];
    $field_name = $this->fieldDefinition->getName();
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    if (!isset($field_state['query_builder'][$delta])) {
      $default_value = $item->get('value')->getValue();
      $field_state['query_builder'][$delta] = $default_value['query_builder'] ?? ['filters' => []];
      static::setWidgetState($parents, $field_name, $form_state, $field_state);
    }

    $filters = $field_state['query_builder'][$delta]['filters'];
    $element['filters'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Filter'),
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('Add a filter to configure it.'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'table-sort-weight',
        ],
      ],
    ];
    foreach ($filters as $plugin_delta => $plugin_config) {
      /** @var \Drupal\search_api_field\Plugin\FilterPluginInterface $plugin */
      $plugin = $this->filterPluginManager->createInstance($plugin_config['plugin'], $plugin_config);

      $subform = &$element['filters'][$plugin_delta];
      $subform = [];
      $subform_state = SubformState::createForSubform($subform, $form, $form_state);

      $element['filters'][$plugin_delta] = [
        'plugin' => $plugin->buildConfigurationForm($subform, $subform_state),
        'weight' => [
          '#type' => 'weight',
          '#default_value' => $plugin_delta,
          '#attributes' => ['class' => ['table-sort-weight']],
        ],
        'remove_button' => [
          '#type' => 'submit',
          '#value' => $this->t('Remove filter'),
          '#name' => 'remove_filter',
          '#submit' => [[$this, 'submitRemoveFilter']],
          '#ajax' => [
            'callback' => [$this, 'ajaxUpdateQueryBuilder'],
            'wrapper' => $wrapper_id,
          ],
          '#limit_validation_errors' => [],
        ],
        '#attributes' => ['class' => 'draggable', 'tabledrag-leaf'],
      ];
    }

    $options = [];
    foreach ($index->getFields() as $field_id => $field) {
      foreach ($this->filterPluginManager->getDefinitionsForField($field) as $plugin_id => $plugin_definition) {
        $options["{$field_id}:{$plugin_id}"] = $plugin_definition['label'] ?? $field->getLabel();
      }
    }

    $element['field'] = [
      '#type' => 'select',
      '#title' => $this->t('Available filters'),
      '#options' => $options,
      '#required' => FALSE,
    ];
    $element['add'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add and configure filter'),
      '#name' => 'add_filter',
      '#submit' => [[$this, 'submitAddFilter']],
      '#ajax' => [
        'callback' => [$this, 'ajaxUpdateQueryBuilder'],
        'wrapper' => $wrapper_id,
      ],
      '#limit_validation_errors' => [
        array_merge(
          $parents,
          [$field_name, $delta, 'wrapper', 'query_builder']
        ),
      ],
    ];

    return $element;
  }

  /**
   * Ajax callback to re-render the query builder wrapper.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form portion to render.
   */
  public function ajaxUpdateQueryBuilder(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $wrapper_parents = array_slice($button['#array_parents'], 0, array_search('query_builder', $button['#array_parents'], TRUE));
    $element = NestedArray::getValue($form, $wrapper_parents);

    return $element['query_builder'];
  }

  /**
   * Submit handler for the add filter button.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitAddFilter(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $wrapper = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));

    // Extract field id and plugin id from the selected option. Since field ids
    // cannot contain special characters, it's safe to explode on the first
    // colon.
    list($field_id, $plugin_id) = explode(':', $form_state->getValue($wrapper['field']['#parents']), 2);

    // Extract element and widget elements.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -3));
    $widget = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -4));

    $field_name = $widget['#field_name'];
    $parents = $widget['#field_parents'];
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    // Add the selected filter to the list for this specific delta.
    $field_state['query_builder'][$element['#delta']]['filters'][] = [
      'plugin' => $plugin_id,
      'field' => $field_id,
    ];
    static::setWidgetState($parents, $field_name, $form_state, $field_state);

    $form_state->setRebuild();
  }

  /**
   * Submit handler for the remove filter button.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitRemoveFilter(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $filter = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $plugin_delta = end($filter['#array_parents']);

    // Extract element and widget elements.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -5));
    $widget = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -6));

    $field_name = $widget['#field_name'];
    $parents = $widget['#field_parents'];
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    unset($field_state['query_builder'][$element['#delta']]['filters'][$plugin_delta]);
    static::setWidgetState($parents, $field_name, $form_state, $field_state);

    $form_state->setRebuild();
  }

  /**
   * Gets the search API index configured in the field storage.
   *
   * @return \Drupal\search_api\IndexInterface|null
   *   The index entity. Null if not found.
   */
  protected function getSearchApiIndex() {
    $index_id = $this->fieldDefinition->getSetting('index');
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $this->entityTypeManager->getStorage('search_api_index')->load($index_id);

    return $index;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    if (empty($values)) {
      return $values;
    }

    $field_name = $this->fieldDefinition->getName();
    $field_state = static::getWidgetState($form['#parents'], $field_name, $form_state);
    $widget = NestedArray::getValue($form_state->getCompleteForm(), $field_state['array_parents']);

    // Clean the values, skipping submitted button values and placing everything
    // under a 'value' array element which will be serialized.
    $cleaned_values = [];
    foreach ($values as $delta => $value) {
      if (!empty($value['wrapper']['fields'])) {
        foreach ($value['wrapper']['fields'] as $fn => $field) {
          $cleaned_values[$delta]['value']['fields'][$fn] = [
            'weight' => $field['weight'],
            'region' => $field['plugin']['type'],
          ];
        }
      }

      $cleaned_values[$delta]['value']['enabled'] = $values[$delta]['enabled'];
      $cleaned_values[$delta]['value']['query_presets'] = $values[$delta]['wrapper']['query_presets'];
      $cleaned_values[$delta]['value']['limit'] = $values[$delta]['wrapper']['limit'];

      if (!empty($values[$delta]['wrapper']['query_builder']['filters'])) {
        $filter_values = $values[$delta]['wrapper']['query_builder']['filters'];
        // Re-order values in case JS is not used.
        uasort($filter_values, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);

        foreach (array_keys($filter_values) as $plugin_delta) {
          $plugin_config = $field_state['query_builder'][$delta]['filters'][$plugin_delta];
          /** @var \Drupal\search_api_field\Plugin\FilterPluginInterface $plugin */
          $plugin = $this->filterPluginManager->createInstance($plugin_config['plugin'], $plugin_config);

          $subform = $widget[$delta]['wrapper']['query_builder']['filters'][$plugin_delta]['plugin'];
          $subform_state = SubformState::createForSubform($subform, $form_state->getCompleteForm(), $form_state);
          $plugin->submitConfigurationForm($subform, $subform_state);

          // Don't specify a key, so that plugins are re-indexed.
          $cleaned_values[$delta]['value']['query_builder']['filters'][] = $plugin->getConfiguration();
        }
      }
    }

    return $cleaned_values;
  }

}
