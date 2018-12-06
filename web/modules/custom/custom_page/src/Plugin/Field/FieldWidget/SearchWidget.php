<?php

namespace Drupal\custom_page\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\search_api_field\Plugin\Field\FieldWidget\SearchWidget as DefaultSearchWidget;
use Drupal\search_api_field\Plugin\FilterPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'search_api_field_custom_page' widget.
 *
 * Adds a checkbox to allow users to include content shared inside the
 * collection, improves labeling and hides unused fields.
 *
 * @FieldWidget(
 *   id = "search_api_field_custom_page",
 *   label = @Translation("Custom page search widget"),
 *   field_types = {
 *     "search_api_field"
 *   }
 * )
 */
class SearchWidget extends DefaultSearchWidget {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $currentUser;

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
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, FilterPluginManagerInterface $filter_plugin_manager, AccountInterface $current_user) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings, $entity_type_manager, $filter_plugin_manager);

    $this->currentUser = $current_user;
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
      $container->get('plugin.manager.search_api_field.filter'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Swap the default label with one that better represents our functionality.
    $element['enabled']['#title'] = $this->t('Display a community content listing');

    // There is no need to allow customizing the facets. For now.
    foreach (['fields', 'refresh_rows', 'refresh'] as $key) {
      $element['wrapper'][$key]['#access'] = FALSE;
    }

    $administrative_access = $this->currentUser->hasPermission('administer search fields');
    foreach (['query_presets', 'limit'] as $key) {
      $element['wrapper'][$key]['#access'] = $administrative_access;
    }

    // If the custom query presets field is filled in, hide also the query
    // builder as users with administrative access are taking care of the
    // query shown in this page.
    if (!$administrative_access && strlen($element['wrapper']['query_presets']['#default_value']) > 0) {
      $element['wrapper']['query_builder']['#access'] = FALSE;
    }

    /** @var \Drupal\search_api_field\Plugin\Field\FieldType\SearchItem $item */
    $item = $items[$delta];
    $default_values = $item->get('value')->getValue();

    $element['wrapper']['show_shared'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show also content shared in the collection'),
      '#description' => $this->t('When this option is unchecked, only content created inside the collection will be shown. When checked, shared content will be included in the listing.'),
      '#default_value' => $default_values['show_shared'] ?? FALSE,
      '#weight' => -10,
    ];

    $element['wrapper']['query_builder']['explanation'] = [
      '#markup' => $this->t("Note: the filters below don't apply any restriction to the suggestions shown. Restrictions are only applied at visualisation time, so only content belonging to or shared in the collection will be shown."),
      '#weight' => -10,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $cleaned_values = parent::massageFormValues($values, $form, $form_state);

    foreach ($values as $delta => $value) {
      $cleaned_values[$delta]['value']['show_shared'] = $values[$delta]['wrapper']['show_shared'];
    }

    return $cleaned_values;
  }

}
