<?php

/**
 * @file
 * Enables modules and site configuration for the Joinup profile.
 */

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Database;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\joinup\JoinupCustomInstallTasks;
use Drupal\joinup\JoinupHelper;
use Drupal\joinup_community_content\CommunityContentHelper;
use Drupal\search_api\Query\QueryInterface;
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
  $form['sparql']['namespace'] = [
    '#type' => 'textfield',
    '#title' => 'Namespace',
    '#default_value' => 'Drupal\\Driver\\Database\\sparql',
    '#required' => TRUE,
  ];

  $form['actions']['save']['#limit_validation_errors'][] = ['sparql'];
  $form['#validate'][] = 'joinup_form_install_settings_validate';
  $form['actions']['save']['#submit'][] = 'joinup_form_install_settings_form_save';
}

/**
 * Validation callback for the installation form.
 *
 * Ensures that the connection class exists.
 */
function joinup_form_install_settings_validate($form, FormStateInterface $form_state) {
  $namespace = $form_state->getValue(['sparql', 'namespace']);
  $class = trim($namespace) . '\\Connection';
  // Try to load the connection class.
  if (!class_exists($class)) {
    $form_state->setError($form['sparql']['namespace'], "Class {$class} could not be detected.");
  }
}

/**
 * Submit callback: Save the Sparql connection string to the settings file.
 */
function joinup_form_install_settings_form_save($form, FormStateInterface $form_state) {
  $host = $form_state->getValue(['sparql', 'host']);
  $port = $form_state->getValue(['sparql', 'port']);
  $namespace = $form_state->getValue(['sparql', 'namespace']);
  // @see rdf_entity.services.yml
  $key = 'sparql_default';
  $target = 'default';
  $database = [
    'prefix' => '',
    'host' => $host,
    'port' => $port,
    'namespace' => $namespace,
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

    // Swap the default user cancel form implementation with a custom one that
    // prevents deleting users when they are the sole owner of a collection.
    $entity_types['user']->setFormClass('cancel', 'Drupal\joinup\Form\UserCancelForm');
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
 * Implements hook_sparql_apply_default_fields_alter().
 *
 * This profile includes 'content_editor' filter format as a text editor and
 * access to 'full_html' and the rest of the filter formats are restricted.
 * With this hook, we make sure that the default fields with type 'text_long'
 * have the 'content_editor' filter format as default.
 */
function joinup_sparql_apply_default_fields_alter($type, &$values) {
  // Since the profile includes a filter format, we provide this as default.
  if ($type == 'text_long') {
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
 * - Disable access to the meta information.
 * - Allow access to the uid field only to the moderators.
 */
function joinup_form_node_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  $form['revision_information']['#access'] = FALSE;
  $form['revision']['#access'] = FALSE;
  $form['meta']['#access'] = FALSE;

  if (isset($form['uid'])) {
    $form['uid']['#access'] = \Drupal::currentUser()->hasPermission('administer nodes');
  }

  foreach (['field_comments', 'field_replies'] as $field) {
    if (!empty($form[$field])) {
      $form[$field]['#access'] = FALSE;
    }
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
 * Implements hook_install_tasks_alter().
 */
function joinup_install_tasks_alter(&$tasks, $install_state) {
  $tasks['joinup_remove_simplenews_defaults'] = [
    'function' => [JoinupCustomInstallTasks::class, 'removeSimpleNewsDefaults'],
  ];
}

/**
 * Implements hook_theme().
 */
function joinup_theme($existing, $type, $theme, $path) {
  $page_template = [
    'variables' => [],
    'path' => drupal_get_path('profile', 'joinup') . '/templates',
  ];
  return [
    'joinup_legal_notice' => $page_template,
    'joinup_eligibility_criteria' => $page_template,
  ];
}

/**
 * Implements hook_preprocess_HOOK() for main menu.
 *
 * Sets the active trail for the main menu items based on the current group
 * context.
 */
function joinup_preprocess_menu__main(&$variables) {
  $group = \Drupal::service('og.context')->getGroup();
  if ($group) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $group */
    switch ($group->bundle()) {
      case 'collection':
        $variables['items']['views_view:views.collections.page_1']['in_active_trail'] = TRUE;
        break;

      case 'solution':
        $variables['items']['views_view:views.solutions.page_1']['in_active_trail'] = TRUE;
        break;
    }
  }

  $variables['#cache']['contexts'][] = 'og_group_context';
  $variables['#cache']['contexts'][] = 'url.path';
}

/**
 * Implements hook_entity_view_alter().
 */
function joinup_entity_view_alter(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display) {
  if (in_array($entity->getEntityTypeId(), ['node', 'rdf_entity'])) {
    // Add the "entity" contextual links group.
    $build['#contextual_links']['entity'] = [
      'route_parameters' => [
        'entity_type' => $entity->getEntityTypeId(),
        'entity' => $entity->id(),
      ],
      'metadata' => ['changed' => $entity->getChangedTime()],
    ];
  }

  // Add the "collection_context" contextual links group on community content
  // and solutions.
  if (JoinupHelper::isSolution($entity) || CommunityContentHelper::isCommunityContent($entity)) {
    // The contextual links need to vary per user roles and per user og roles.
    // Core already takes care of varying by roles by applying the
    // user.permissions cache context and applying the permission hash in the
    // contextual links. We need to include the corresponding data deriving from
    // the og role cache context.
    /** @var \Drupal\og\Cache\Context\OgRoleCacheContext $cache_service */
    $cache_service = \Drupal::service('cache_context.og_role');
    $roles_hash = $cache_service->getContext();

    // The rendered entity needs to vary by og group context.
    $build['#cache']['contexts'] = Cache::mergeContexts($build['#cache']['contexts'], [
      'og_role',
      'og_group_context',
    ]);
    $build['#contextual_links']['collection_context'] = [
      'route_parameters' => [
        'entity_type' => $entity->getEntityTypeId(),
        'entity' => $entity->id(),
        // The collection parameter is a required parameter in the pin/unpin
        // routes. If the parameter is left empty, a critical exception will
        // occur and the contextual links generation will break. By passing an
        // empty value, an upcast exception will be catched and the access
        // checks will correctly return an access denied.
        'collection' => NULL,
      ],
      'metadata' => [
        'changed' => $entity->getChangedTime(),
        'og_roles_hash' => $roles_hash,
      ],
    ];
    /** @var \Drupal\rdf_entity\RdfInterface $collection */
    $collection = \Drupal::service('og.context')->getGroup();
    if ($collection && JoinupHelper::isCollection($collection)) {
      $build['#contextual_links']['collection_context']['route_parameters']['collection'] = $collection->id();
      $build['#contextual_links']['collection_context']['metadata']['collection_changed'] = $collection->getChangedTime();
    }
  }
}

/**
 * Implements hook_preprocess_rdf_entity().
 */
function joinup_preprocess_rdf_entity(&$variables) {
  _joinup_preprocess_entity_tiles($variables);
}

/**
 * Implements hook_preprocess_node().
 */
function joinup_preprocess_node(&$variables) {
  _joinup_preprocess_entity_tiles($variables);
}

/**
 * Adds common functionality to the tile view mode of nodes and rdf entities.
 *
 * @param array $variables
 *   The variables array.
 */
function _joinup_preprocess_entity_tiles(array &$variables) {
  if ($variables['view_mode'] !== 'view_mode_tile') {
    return;
  }

  /** @var \Drupal\Core\Entity\ContentEntityBase $entity */
  $entity = $variables[$variables['elements']['#entity_type']];

  // If the entity has the site-wide featured field, enable the related js
  // library.
  if ($entity->hasField('field_site_featured') && $entity->get('field_site_featured')->value) {
    $variables['attributes']['data-drupal-featured'][] = TRUE;
    $variables['#attached']['library'][] = 'joinup/site_wide_featured';
  }

  /** @var \Drupal\joinup\PinServiceInterface $pin_service */
  $pin_service = \Drupal::service('joinup.pin_service');
  if ($pin_service->isEntityPinned($entity)) {
    $variables['attributes']['class'][] = 'is-pinned';
    $variables['#attached']['library'][] = 'joinup/pinned_entities';

    if (JoinupHelper::isSolution($entity)) {
      $collection_ids = [];
      foreach ($pin_service->getCollectionsWherePinned($entity) as $collection) {
        $collection_ids[] = $collection->id();
      }
      $variables['attributes']['data-drupal-pinned-in'] = implode(',', $collection_ids);
    }
  }
}

/**
 * Implements hook_search_api_query_TAG_alter().
 *
 * When the content overview view is being filtered on events, change the
 * sorting to be by event date.
 */
function joinup_search_api_query_views_content_overview_alter(QueryInterface &$query) {
  $facets = _joinup_get_facets_by_facet_source_id('search_api:views_page__content_overview__page_1');

  // No further processing is needed if we are not filtering on events.
  if (!isset($facets['content_bundle']) || !$facets['content_bundle']->isActiveValue('event')) {
    return;
  }

  $sorts = &$query->getSorts();
  // When filtering for upcoming events, show first the events that are going
  // to happen sooner.
  $order = isset($facets['event_date']) && $facets['event_date']->isActiveValue('upcoming_events') ? QueryInterface::SORT_ASC : QueryInterface::SORT_DESC;
  $sorts = [
    'field_event_date' => $order,
  ] + $sorts;
}

/**
 * Implements hook_views_pre_execute().
 *
 * Sets the view max age to tomorrow midnight when filtering down for upcoming
 * or past events.
 */
function joinup_views_pre_execute(ViewExecutable $view) {
  $facets = _joinup_get_facets_by_facet_source_id('search_api:views_page__content_overview__page_1');

  if (
    !isset($facets['event_date']) ||
    empty(array_intersect($facets['event_date']->getActiveItems(), ['upcoming_events', 'past_events']))
  ) {
    return;
  }

  $max_age = (new DrupalDateTime('tomorrow'))->getTimestamp() - \Drupal::time()->getRequestTime();
  $view->display_handler->display['cache_metadata']['max-age'] = $max_age;
}

/**
 * Returns currently rendered facets filtered by facet source ID, keyed by ID.
 *
 * @param string $facet_source_id
 *   The facet source ID to filter by.
 *
 * @return \Drupal\facets\FacetInterface[]
 *   An array of facet, keyed by facet ID.
 */
function _joinup_get_facets_by_facet_source_id($facet_source_id) {
  /** @var \Drupal\facets\FacetManager\DefaultFacetManager $facet_manager */
  $facet_manager = \Drupal::service('facets.manager');

  /** @var \Drupal\facets\FacetInterface[] $facets */
  $facets = [];
  foreach ($facet_manager->getFacetsByFacetSourceId($facet_source_id) as $facet) {
    $facets[$facet->id()] = $facet;
  }

  return $facets;
}
