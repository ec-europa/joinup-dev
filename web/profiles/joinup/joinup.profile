<?php

/**
 * @file
 * Enables modules and site configuration for the Joinup profile.
 */

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\joinup\JoinupCustomInstallTasks;
use Drupal\views\ViewExecutable;

/**
 * Implements hook_form_FORMID_alter().
 *
 * Add the Sparql endpoint fields to the configure database install step.
 */
function joinup_form_install_settings_form_alter(&$form, FormStateInterface $form_state) {
  $form['sparql'] = [
    '#type' => 'fieldset',
    '#title' => 'Sparql endpoint',
    '#tree' => TRUE,
  ];
  $form['sparql']['host'] = [
    '#type' => 'textfield',
    '#title' => 'Host',
    '#default_value' => 'localhost',
    '#size' => 45,
    '#required' => TRUE,
  ];
  $form['sparql']['port'] = [
    '#type' => 'number',
    '#title' => 'Port',
    '#default_value' => '8890',
    '#min' => 0,
    '#max' => 65535,
    '#required' => TRUE,
  ];

  $form['actions']['save']['#limit_validation_errors'][] = ['sparql'];
  $form['actions']['save']['#submit'][] = 'joinup_form_install_settings_form_save';
}

/**
 * Submit callback: Save the Sparql connection string to the settings file.
 */
function joinup_form_install_settings_form_save($form, FormStateInterface $form_state) {
  $host = $form_state->getValue(['sparql', 'host']);
  $port = $form_state->getValue(['sparql', 'port']);
  // @see rdf_entity.services.yml
  $key = 'sparql_default';
  $target = 'default';
  $database = [
    'prefix' => '',
    'host' => $host,
    'port' => $port,
    'namespace' => 'Drupal\\rdf_entity\\Database\\Driver\\sparql',
    'driver' => 'sparql',
  ];
  $settings['databases'][$key][$target] = (object) [
    'value' => $database,
    'required' => TRUE,
  ];
  drupal_rewrite_settings($settings);
  // Load the database connection to make it available in the current request.
  Database::addConnectionInfo($key, $target, $database);
}

/**
 * Implements hook_entity_type_alter().
 */
function joinup_entity_type_alter(array &$entity_types) {
  // Add the "Propose" form operation to nodes and RDF entities so that we can
  // add propose form displays to them.
  // Skip this during installation, since the RDF entity will not yet be
  // registered.
  if (!drupal_installation_attempted()) {
    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
    $entity_types['rdf_entity']->setFormclass('propose', 'Drupal\rdf_entity\Form\RdfForm');
  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function joinup_form_field_config_edit_form_alter(&$form) {
  // Increase the maximum length of the file extension field to allow
  // registration of large amounts of extensions.
  if (isset($form['settings']['file_extensions']['#maxlength'])) {
    $form['settings']['file_extensions']['#maxlength'] = 1024;
  }
}

/**
 * Implements hook_rdf_apply_default_fields_alter().
 *
 * This profile includes 'content_editor' filter format as a text editor and
 * access to 'full_html' and the rest of the filter formats are restricted.
 * With this hook, we make sure that the default fields with type 'text_long'
 * have the 'content_editor' filter format as default.
 */
function joinup_rdf_apply_default_fields_alter(FieldStorageConfig $storage, &$values) {
  // Since the profile includes a filter format, we provide this as default.
  if ($storage->getType() == 'text_long') {
    foreach ($values as &$value) {
      if ($value['format'] == 'full_html') {
        $value['format'] = 'content_editor';
      }
    }
  }
}

/**
 * Implements hook_og_user_access_alter().
 */
function joinup_og_user_access_alter(&$permissions, &$cacheable_metadata, $context) {
  // Moderators should have access to view, create, edit and delete all group
  // content in collections.
  /** @var \Drupal\Core\Session\AccountProxyInterface $user */
  $user = $context['user'];
  $operation = $context['operation'];
  $group = $context['group'];

  $is_moderator = in_array('moderator', $user->getRoles());
  $is_collection = $group->bundle() === 'collection';
  $operation_allowed = in_array($operation, [
    'view',
    'create',
    'update',
    'delete',
  ]);

  if ($is_moderator && $is_collection && $operation_allowed) {
    $permissions[] = $operation;
  }
}

/**
 * Implements hook_entity_access().
 */
function joinup_entity_access(EntityInterface $entity, $operation, AccountInterface $account) {
  // Moderators have the 'administer group' permission so they can manage all
  // group content across all groups. However since the OG Menu entities are
  // also group content moderators are also granted access to the OG Menu
  // administration pages. Let's specifically deny access to these, since we are
  // handling the menu items transparently whenever custom pages are created or
  // deleted. Moderators and collection facilitators should only have access to
  // the edit form of an OG Menu instance so they can rearrange the custom
  // pages, but not to the entity forms of the menu items themselves.
  // In fact, nobody should have access to these pages except UID 1.
  if ($entity->getEntityTypeId() === 'ogmenu_instance' && $operation !== 'update') {
    return AccessResult::forbidden();
  }
}

/**
 * Implements hook_field_widget_inline_entity_form_complex_form_alter().
 *
 * Simplifies the widget buttons when only a bundle is configured.
 */
function joinup_field_widget_inline_entity_form_complex_form_alter(&$element, FormStateInterface $form_state, $context) {
  if (isset($element['actions']['bundle']['#type']) && $element['actions']['bundle']['#type'] == 'value') {
    $buttons = [
      'ief_add' => t('Add new'),
      'ief_add_existing' => t('Add existing'),
    ];

    foreach ($buttons as $key => $label) {
      if (!empty($element['actions'][$key])) {
        $element['actions'][$key]['#value'] = $label;
      }
    }
  }

  // If no title is provided for the fieldset wrapping the create form, add the
  // label of the bundle of the entity being created.
  if (empty($element['form']['#title']) && !empty($element['form']['inline_entity_form']['#bundle'])) {
    $entity_type = $element['form']['inline_entity_form']['#entity_type'];
    $bundle = $element['form']['inline_entity_form']['#bundle'];

    $bundle_info = \Drupal::entityManager()->getBundleInfo($entity_type);
    $element['form']['#title'] = $bundle_info[$bundle]['label'];
  }
}

/**
 * Implements hook_inline_entity_form_reference_form_alter().
 */
function joinup_inline_entity_form_reference_form_alter(&$reference_form, &$form_state) {
  // Avoid showing two labels one after each other.
  $reference_form['entity_id']['#title_display'] = 'invisible';
}

/**
 * Implements hook_form_FORM_ID_alter().
 *
 * - Disable access to the revision information vertical tab.
 *   This prevents access to the revision log and the revision checkbox too.
 * - Disable access to the comment settings. These are managed on collection
 *   level.
 */
function joinup_form_node_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  $form['revision_information']['#access'] = FALSE;
  $form['revision']['#access'] = FALSE;

  if (!empty($form['field_comments'])) {
    $form['field_comments']['#access'] = FALSE;
  }
}

/**
 * Implements hook_field_formatter_third_party_settings_form().
 *
 * Allow adding template suggestions for each field.
 */
function joinup_field_formatter_third_party_settings_form(FormatterInterface $plugin, FieldDefinitionInterface $field_definition, $view_mode, $form, FormStateInterface $form_state) {
  $element = [];

  $element['template_suggestion'] = [
    '#type' => 'textfield',
    '#title' => t('Template suggestion'),
    '#size' => 64,
    '#field_prefix' => 'field__',
    '#default_value' => $plugin->getThirdPartySetting('joinup', 'template_suggestion'),
  ];

  return $element;
}

/**
 * Implements hook_theme_suggestions_field_alter().
 *
 * Add template suggestions based on the configuration added in the formatter.
 */
function joinup_theme_suggestions_field_alter(array &$suggestions, array &$variables) {
  $element = $variables['element'];

  if (!empty($element['#entity_type']) && !empty($element['#bundle']) && !empty($element['#field_name'])) {
    $entity_type = $element['#entity_type'];
    $bundle = $element['#bundle'];
    $field_name = $element['#field_name'];
    // View mode is not strictly required for the functionality.
    $view_mode = !empty($element['#view_mode']) ? $element['#view_mode'] : 'default';

    // Load the related display. If not found, try to load the default as
    // fallback. This is needed because displays like the "full" one might not
    // be enabled but still used for rendering.
    // @see \Drupal\Core\Entity\Entity\EntityViewDisplay::collectRenderDisplays()
    $display = EntityViewDisplay::load($entity_type . '.' . $bundle . '.' . $view_mode);
    if (empty($display) && $view_mode !== 'default') {
      $display = EntityViewDisplay::load($entity_type . '.' . $bundle . '.default');
    }

    if (!empty($display)) {
      $component = $display->getComponent($field_name);
      if (!empty($component['third_party_settings']['joinup']['template_suggestion'])) {
        $suggestion = 'field__' . $component['third_party_settings']['joinup']['template_suggestion'];
        $suggestions[] = $suggestion;
        $suggestions[] = $suggestion . '__' . $entity_type;
        $suggestions[] = $suggestion . '__' . $entity_type . '__' . $bundle;
        $suggestions[] = $suggestion . '__' . $entity_type . '__' . $bundle . '__' . $field_name;
        $suggestions[] = $suggestion . '__' . $entity_type . '__' . $bundle . '__' . $field_name . '__' . $view_mode;

        // Add the custom template suggestion back in the element to allow other
        // modules to have this information.
        $variables['element']['#joinup_template_suggestion'] = $suggestion;
      }
    }
  }
}

/**
 * Implements hook_views_pre_view().
 */
function joinup_views_pre_view(ViewExecutable $view) {
  // The collections overview varies by the user's memberships. For example if
  // you are the owner of a proposed collection you can see it, while a non-
  // member won't be able to see it yet.
  // Note that for page displays this currently only affects the query result
  // cache in Views, not the render cache. ViewPageController::handle() only
  // sets a cache context when contextual links are enabled.
  // @todo Solve this properly on render cache level by providing a dedicated
  //   property like _view_display_cache_contexts on the router object which is
  //   created in PathPluginBase::getRoute(). We can then use this to output the
  //   correct cache contexts in ViewPageController::handle().
  // @see https://www.drupal.org/node/2839058
  if (in_array($view->id(), ['collections', 'solutions', 'content_overview'])) {
    $view->display_handler->display['cache_metadata']['contexts'][] = 'og_role';
    $view->display_handler->display['cache_metadata']['contexts'][] = 'user.roles';
  }
}

/**
 * Implements hook_install_tasks_alter().
 */
function joinup_install_tasks_alter(&$tasks, $install_state) {
  $tasks['joinup_remove_simplenews_defaults'] = [
    'function' => [JoinupCustomInstallTasks::class, 'removeSimpleNewsDefaults'],
  ];
}
