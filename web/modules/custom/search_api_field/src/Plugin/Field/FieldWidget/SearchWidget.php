<?php

namespace Drupal\search_api_field\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\Entity\Facet;
use Drupal\link\LinkItemInterface;
use Drupal\search_api_field\Plugin\Field\FieldType\SearchItem;

/**
 * Plugin implementation of the 'link' widget.
 *
 * @FieldWidget(
 *   id = "search_default",
 *   label = @Translation("Link"),
 *   field_types = {
 *     "search"
 *   }
 * )
 */
class SearchWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'placeholder_url' => '',
      'placeholder_title' => '',
    ) + parent::defaultSettings();
  }

  /**
   * Gets the URI without the 'internal:' or 'entity:' scheme.
   *
   * The following two forms of URIs are transformed:
   * - 'entity:' URIs: to entity autocomplete ("label (entity id)") strings;
   * - 'internal:' URIs: the scheme is stripped.
   *
   * This method is the inverse of ::getUserEnteredStringAsUri().
   *
   * @param string $uri
   *   The URI to get the displayable string for.
   *
   * @return string
   *   The displayable string.
   *
   * @see static::getUserEnteredStringAsUri()
   */
  protected static function getUriAsDisplayableString($uri) {
    $scheme = parse_url($uri, PHP_URL_SCHEME);

    // By default, the displayable string is the URI.
    $displayable_string = $uri;

    // A different displayable string may be chosen in case of the 'internal:'
    // or 'entity:' built-in schemes.
    if ($scheme === 'internal') {
      $uri_reference = explode(':', $uri, 2)[1];

      // @todo '<front>' is valid input for BC reasons, may be removed by
      //   https://www.drupal.org/node/2421941
      $path = parse_url($uri, PHP_URL_PATH);
      if ($path === '/') {
        $uri_reference = '<front>' . substr($uri_reference, 1);
      }

      $displayable_string = $uri_reference;
    }
    elseif ($scheme === 'entity') {
      list($entity_type, $entity_id) = explode('/', substr($uri, 7), 2);
      // Show the 'entity:' URI as the entity autocomplete would.
      $entity_manager = \Drupal::entityManager();
      if ($entity_manager->getDefinition($entity_type, FALSE) && $entity = \Drupal::entityManager()
          ->getStorage($entity_type)
          ->load($entity_id)
      ) {
        $displayable_string = EntityAutocomplete::getEntityLabels(array($entity));
      }
    }

    return $displayable_string;
  }

  /**
   * Gets the user-entered string as a URI.
   *
   * The following two forms of input are mapped to URIs:
   * - entity autocomplete ("label (entity id)") strings: to 'entity:' URIs;
   * - strings without a detectable scheme: to 'internal:' URIs.
   *
   * This method is the inverse of ::getUriAsDisplayableString().
   *
   * @param string $string
   *   The user-entered string.
   *
   * @return string
   *   The URI, if a non-empty $uri was passed.
   *
   * @see static::getUriAsDisplayableString()
   */
  protected static function getUserEnteredStringAsUri($string) {
    // By default, assume the entered string is an URI.
    $uri = $string;

    // Detect entity autocomplete string, map to 'entity:' URI.
    $entity_id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($string);
    if ($entity_id !== NULL) {
      // @todo Support entity types other than 'node'. Will be fixed in
      //   https://www.drupal.org/node/2423093.
      $uri = 'entity:node/' . $entity_id;
    }
    // Detect a schemeless string, map to 'internal:' URI.
    elseif (!empty($string) && parse_url($string, PHP_URL_SCHEME) === NULL) {
      // @todo '<front>' is valid input for BC reasons, may be removed by
      //   https://www.drupal.org/node/2421941
      // - '<front>' -> '/'
      // - '<front>#foo' -> '/#foo'
      if (strpos($string, '<front>') === 0) {
        $string = '/' . substr($string, strlen('<front>'));
      }
      $uri = 'internal:' . $string;
    }

    return $uri;
  }

  /**
   * Form element validation handler for the 'uri' element.
   *
   * Disallows saving inaccessible or untrusted URLs.
   */
  public static function validateUriElement($element, FormStateInterface $form_state, $form) {
    $uri = static::getUserEnteredStringAsUri($element['#value']);
    $form_state->setValueForElement($element, $uri);

    // If getUserEnteredStringAsUri() mapped the entered value to a 'internal:'
    // URI , ensure the raw value begins with '/', '?' or '#'.
    // @todo '<front>' is valid input for BC reasons, may be removed by
    //   https://www.drupal.org/node/2421941
    if (
      parse_url($uri, PHP_URL_SCHEME) === 'internal'
      && !in_array($element['#value'][0], ['/', '?', '#'], TRUE)
      && substr($element['#value'], 0, 7) !== '<front>'
    ) {
      $form_state->setError($element, t('Manually entered paths should start with /, ? or #.'));
      return;
    }
  }

  /**
   * Form element validation handler for the 'title' element.
   *
   * Conditionally requires the link title if a URL value was filled in.
   */
  public static function validateTitleElement(&$element, FormStateInterface $form_state, $form) {
    if ($element['uri']['#value'] !== '' && $element['title']['#value'] === '') {
      $element['title']['#required'] = TRUE;
      // We expect the field name placeholder value to be wrapped in t() here,
      // so it won't be escaped again as it's already marked safe.
      $form_state->setError($element['title'], t('@name field is required.', array('@name' => $element['title']['#title'])));
    }
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

    $element += array(
      '#extra' => array_keys($facets),
    );

    $table = array(
      '#type' => 'field_ui_table',
      '#header' => $this->getTableHeader(),
      '#regions' => $this->getRegions(),
      '#attributes' => array(
        'class' => array('field-ui-overview'),
        'id' => 'field-display-overview',
      ),
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'field-weight',
        ),
        array(
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'field-parent',
          'subgroup' => 'field-parent',
          'source' => 'field-name',
        ),
      ),
    );

    $form['#attached']['library'][] = 'search_api_field/drupal.search_api_field';
    foreach ($facets as $facet_id => $facet) {
      $table[$facet_id] = $this->buildFacetRow($facet, $item);
    }
    $element['fields'] = $table;
    $element['refresh_rows'] = array('#type' => 'hidden');
    $element['refresh'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Refresh'),
      '#op' => 'refresh_table',
      '#submit' => array('::multistepSubmit'),
      '#ajax' => array(
        'callback' => '::multistepAjax',
        'wrapper' => 'field-display-overview-wrapper',
        'effect' => 'fade',
        // The button stays hidden, so we hide the Ajax spinner too. Ad-hoc
        // spinners will be added manually by the client-side script.
        'progress' => 'none',
      ),
      '#attributes' => array(
        'class' => array(
          'visually-hidden',
          'row-refresher',
        ),
      ),
    );

    $default = $item->get('value')->getValue();
    $element['show_textfield'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show full-text search field.'),
      '#default_value' => !empty($default['show_textfield']) ? $default['show_textfield'] : FALSE,
    ];

    return $element;
  }

  /**
   * The region this row belongs to.
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
    $value = $item->get('value');
    $areas = $value->getValue()['fields'];
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
    $facet_storage = \Drupal::entityTypeManager()->getStorage('facets_facet');
    $facets = $facet_storage->loadByProperties(['facet_source_id' => 'search_api_field:' . $storage->id()]);
    return $facets;
  }

  /**
   * Indicates enabled support for link to routes.
   *
   * @return bool
   *   Returns TRUE if the LinkItem field is configured to support links to
   *   routes, otherwise FALSE.
   */
  protected function supportsInternalLinks() {
    $link_type = $this->getFieldSetting('link_type');
    return (bool) ($link_type & LinkItemInterface::LINK_INTERNAL);
  }

  /**
   * Indicates enabled support for link to external URLs.
   *
   * @return bool
   *   Returns TRUE if the LinkItem field is configured to support links to
   *   external URLs, otherwise FALSE.
   */
  protected function supportsExternalLinks() {
    $link_type = $this->getFieldSetting('link_type');
    return (bool) ($link_type & LinkItemInterface::LINK_EXTERNAL);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['placeholder_url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder for URL'),
      '#default_value' => $this->getSetting('placeholder_url'),
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    );
    $elements['placeholder_title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder for link text'),
      '#default_value' => $this->getSetting('placeholder_title'),
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
      '#states' => array(
        'invisible' => array(
          ':input[name="instance[settings][title]"]' => array('value' => DRUPAL_DISABLED),
        ),
      ),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $placeholder_title = $this->getSetting('placeholder_title');
    $placeholder_url = $this->getSetting('placeholder_url');
    if (empty($placeholder_title) && empty($placeholder_url)) {
      $summary[] = $this->t('No placeholders');
    }
    else {
      if (!empty($placeholder_title)) {
        $summary[] = $this->t('Title placeholder: @placeholder_title', array('@placeholder_title' => $placeholder_title));
      }
      if (!empty($placeholder_url)) {
        $summary[] = $this->t('URL placeholder: @placeholder_url', array('@placeholder_url' => $placeholder_url));
      }
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    if (empty($values)) {
      return;
    }
    $ordered_values = [];
    foreach ($values as $delta => $value) {
      $ordered_values[$delta]['value']['show_textfield'] = $value['show_textfield'];

      if (!empty($value['fields'])) {
        foreach ($value['fields'] as $fn => $field) {
          $ordered_values[$delta]['value']['fields'][$fn] = [
            'weight' => $field['weight'],
            'region' => $field['plugin']['type'],
          ];
        }
      }
    }
    return $ordered_values;
  }

}
