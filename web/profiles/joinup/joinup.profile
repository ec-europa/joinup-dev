<?php

/**
 * @file
 * Enables modules and site configuration for the Joinup profile.
 */

declare(strict_types = 1);

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Database;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Installer\InstallerKernel;
use Drupal\Core\Session\AccountInterface;
use Drupal\collection\Entity\CollectionInterface;
use Drupal\joinup_community_content\Entity\CommunityContentInterface;
use Drupal\joinup_featured\FeaturedContentInterface;
use Drupal\joinup_group\Entity\PinnableGroupContentInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\solution\Entity\SolutionInterface;
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
    '#default_value' => 'Drupal\\joinup_sparql\\Driver\\Database\\joinup_sparql',
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
  if (!InstallerKernel::installationAttempted()) {
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
 * Implements hook_entity_access().
 */
function joinup_entity_access(EntityInterface $entity, $operation, AccountInterface $account) {
  // Moderators have the 'administer organic groups' permission so they can
  // manage all group content across all groups. However since the OG Menu
  // entities are also group content moderators are also granted access to the
  // OG Menu administration pages. Let's specifically deny access to these,
  // since we are handling the menu items transparently whenever custom pages
  // are created or deleted. Moderators and collection facilitators should only
  // have access to the edit form of an OG Menu instance so they can rearrange
  // the custom pages, but not to the entity forms of the menu items themselves.
  // In fact, nobody should have access to these pages except UID 1.
  if ($entity->getEntityTypeId() === 'ogmenu_instance' && $operation !== 'update') {
    return AccessResult::forbidden();
  }

  return AccessResult::neutral();
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

    $bundle_info = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type);
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
 * Implements hook_theme().
 */
function joinup_theme($existing, $type, $theme, $path) {
  $page_template = [
    'variables' => [],
    'path' => drupal_get_path('profile', 'joinup') . '/templates',
  ];
  return [
    'joinup_eligibility_criteria' => $page_template,
    'joinup_legal_notice' => $page_template,
    'joinup_modal_close_button' => [
      'variables' => [
        'label' => t('Got it'),
        'url' => '',
        'attributes' => [],
      ],
    ] + $page_template,
  ];
}

/**
 * Implements hook_entity_view_alter().
 *
 * Adds metadata needed to show relevant contextual links whenever entities are
 * displayed.
 */
function joinup_entity_view_alter(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display) {
  if (in_array($entity->getEntityTypeId(), ['node', 'rdf_entity'])) {
    // Add the "entity" contextual links group. Avoid overwriting any existing
    // data that might have been added by other modules. Because of a bug in
    // Drupal core we cannot alter the running order of hook_entity_view_alter()
    // when it is executed as an "extra hook" for hook_rdf_entity_view_alter().
    // This means that we cannot use hook_module_implements_alter() to define a
    // proper running order for this hook, so let's make sure that we do not
    // lose any data set by other modules which are supposed to run after us.
    // @see joinup_featured_entity_view_alter()
    // @see https://www.drupal.org/project/drupal/issues/3120298
    $build['#contextual_links']['entity']['route_parameters']['entity_type'] = $entity->getEntityTypeId();
    $build['#contextual_links']['entity']['route_parameters']['entity'] = $entity->id();
    $build['#contextual_links']['entity']['metadata']['changed'] = $entity->getChangedTime();
  }

  if (!$entity instanceof PinnableGroupContentInterface) {
    return;
  }

  // The contextual links vary per user roles (since moderators are able to pin
  // content) and per OG roles (since facilitators are able to pin content).
  // Core already takes care of varying by roles by applying the
  // user.permissions cache context and applying the permission hash in the
  // contextual links. We need to include the corresponding data deriving from
  // the og_role cache context.
  /** @var \Drupal\og\Cache\Context\OgRoleCacheContext $cache_service */
  $cache_service = \Drupal::service('cache_context.og_role');
  $roles_hash = $cache_service->getContext();

  // The rendered entity needs to vary by OG group context.
  $build['#cache']['contexts'] = Cache::mergeContexts($build['#cache']['contexts'], [
    'og_role',
    'og_group_context',
  ]);

  /** @var \Drupal\joinup_group\Entity\GroupInterface $group */
  $group = \Drupal::service('og.context')->getGroup();

  // The existence of the group context contextual links helps with enforcing
  // the og_context to the entity because otherwise there is nothing in the view
  // itself that would invalidate the tile and make it really `og_role`
  // dependant.
  $build['#contextual_links']['group_context'] = [
    'route_parameters' => [
      'entity_type' => $entity->getEntityTypeId(),
      'entity' => $entity->id(),
      // The group parameter is a required parameter in the pin/unpin
      // routes. If the parameter is left empty, a critical exception will
      // occur and the contextual links generation will break. By passing an
      // empty value, an upcast exception will be catched and the access
      // checks will correctly return an access denied.
      'group' => NULL,
    ],
    'metadata' => [
      'changed' => $entity->getChangedTime(),
      'og_roles_hash' => $roles_hash,
      'pin_status' => $entity->isPinned($group),
    ],
  ];

  // The next check asserts that the group is either a collection or a solution
  // but for solutions, only community content are allowed to be pinned, not
  // related solutions.
  if ($group && ($group instanceof CollectionInterface || $entity instanceof CommunityContentInterface && $group instanceof SolutionInterface)) {
    // Used by the contextual links for pinning/unpinning entity in group.
    // @see: joinup.pin_entity, joinup.unpin_entity routes.
    $build['#contextual_links']['group_context']['route_parameters']['group'] = $group->id();
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
  $entity = $variables[$variables['elements']['#entity_type']] ?? NULL;
  if (empty($entity)) {
    return;
  }

  // If the entity is featured site-wide, enable the related JS library.
  if ($entity instanceof FeaturedContentInterface && $entity->isFeatured()) {
    $variables['attributes']['data-drupal-featured'][] = TRUE;
    $variables['#attached']['library'][] = 'joinup/site_wide_featured';
  }

  $context = \Drupal::service('og.context')->getRuntimeContexts(['og']);
  $group = NULL;
  if (!empty($context['og'])) {
    $group = $context['og']->getContextValue();
  }

  if ($entity instanceof PinnableGroupContentInterface && $entity->isPinned($group)) {
    $variables['attributes']['class'][] = 'is-pinned';
    $variables['#attached']['library'][] = 'joinup/pinned_entities';

    $group_ids = $entity->getPinnedGroupIds();
    $variables['attributes']['data-drupal-pinned-in'] = implode(',', $group_ids);
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
    empty(array_intersect(
      $facets['event_date']->getActiveItems(),
      ['upcoming_events', 'past_events']
    ))
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
