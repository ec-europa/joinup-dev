<?php

namespace Drupal\search_api_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\ParseMode\ParseModePluginManager;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Query\ResultSetInterface;
use Drupal\search_api\SearchApiException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Plugin implementation of the 'search_api_field' formatter.
 *
 * @FieldFormatter(
 *   id = "search_api_field",
 *   label = @Translation("Search"),
 *   field_types = {
 *     "search_api_field"
 *   }
 * )
 */
class SearchFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The query parse mode manager.
   *
   * @var \Drupal\search_api\ParseMode\ParseModePluginManager
   */
  protected $parseModeManager;

  /**
   * The current request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs a SearchFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   * @param \Drupal\search_api\ParseMode\ParseModePluginManager $parse_mode_manager
   *   The query parse mode manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, Request $request, ParseModePluginManager $parse_mode_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->entityTypeManager = $entity_type_manager;
    $this->parseModeManager = $parse_mode_manager;
    $this->request = $request;
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
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('plugin.manager.search_api.parse_mode')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $entity = $items->getEntity();
    $field_definition = $this->fieldDefinition;

    // Avoid infinite recursion when a search node is shown as a result.
    if ($entity->search_api_field_do_not_recurse) {
      return [];
    }

    // At the moment, this formatter supports only single-value fields.
    $settings_item = $items->first();
    if (empty($settings_item)) {
      return [];
    }
    $settings = $settings_item->value;
    // Bail out if the field is disabled.
    if (empty($settings['enabled'])) {
      return [];
    }

    $index_id = $field_definition->getSetting('index');
    /* @var $search_api_index \Drupal\search_api\IndexInterface */
    $search_api_index = Index::load($index_id);

    if (empty($search_api_index)) {
      throw new SearchApiException("Could not load index with ID '$index_id'.");
    }

    $limit = !empty($settings['limit']) ? $settings['limit'] : 10;

    $options = [
      'limit' => $limit,
      'offset' => !is_null($this->request->get('page')) ? $this->request->get('page') * $limit : 0,
      'search_api_field entity' => $entity,
      'search_api_field item' => $items->first(),
    ];
    $query = $search_api_index->query($options);
    $query->setSearchId($field_definition->getTargetEntityTypeId() . '.' . $field_definition->getName());
    $query->setParseMode($this->parseModeManager->createInstance('direct'));

    if (!empty($settings['query_presets'])) {
      $this->applyPresets($query, $settings['query_presets']);
    }

    $hooks = [
      'search_api_field_',
      'search_api_field_' . $field_definition->getName(),
    ];
    foreach ($hooks as $hook) {
      $query->addTag($hook);
    }

    $result = $query->execute();
    $render = $this->renderSearchResults($result, $limit);
    $tags = [];
    // Check the search index for entity data sources,
    // and add all as cache tags.
    foreach ($search_api_index->getDatasources() as $datasource) {
      $plugin_def = $datasource->getPluginDefinition();
      if ($plugin_def['id'] != 'entity') {
        continue;
      }
      $entity_type_id = $plugin_def['entity_type'];
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      $list_tags = $entity_type->getListCacheTags();
      $tags = Cache::mergeTags($tags, $list_tags);
    }
    $render['#cache'] = [
      'tags' => $tags,
      'contexts' => ['url.path'],
    ];

    // Add some information about the field.
    // @see \Drupal\Core\Field\FormatterBase::view()
    $entity = $items->getEntity();
    $render += [
      '#entity_type' => $entity->getEntityTypeId(),
      '#bundle' => $entity->bundle(),
      '#field_name' => $this->fieldDefinition->getName(),
      '#entity' => $entity,
    ];

    return $render;
  }

  /**
   * Builds a renderable array for the search results.
   *
   * @param \Drupal\search_api\Query\ResultSetInterface $result
   *   The query results object.
   * @param int $limit
   *   The number of results to show for each page.
   *
   * @return array
   *   The render array for the search results.
   */
  protected function renderSearchResults(ResultSetInterface $result, $limit) {
    $view_mode_settings = $this->fieldDefinition->getSetting('view_modes');

    $results = [];
    /* @var $item \Drupal\search_api\Item\ItemInterface */
    foreach ($result->getResultItems() as $item) {
      try {
        /** @var \Drupal\Core\Entity\EntityInterface $entity */
        $entity = $item->getOriginalObject()->getValue();
      }
      catch (SearchApiException $e) {
        $entity = NULL;
      }
      // Search results might be stale, so we check if the entity has been
      // found in the system.
      if (!$entity) {
        continue;
      }
      if (!$entity->access('view')) {
        continue;
      }

      // Avoid recursions when an entity contains another search field.
      $entity->search_api_field_do_not_recurse = TRUE;

      // Use the view mode configured in the field type settings or fallback
      // to default view mode.
      $entity_type = $entity->getEntityTypeId();
      $entity_bundle = $entity->bundle();
      $datasource_id = 'entity:' . $entity_type;
      if (!empty($view_mode_settings[$datasource_id][$entity_bundle])) {
        $view_mode = $view_mode_settings[$datasource_id][$entity_bundle];
      }
      else {
        $view_mode = 'default';
      }

      $results[] = [
        '#theme' => 'search_api_field_result',
        '#item' => $this->entityTypeManager->getViewBuilder($entity_type)->view($entity, $view_mode),
        '#entity' => $entity,
      ];
    }

    $build = [
      '#theme' => 'search_api_field',
    ];

    if (!empty($results)) {
      $build += [
        '#search_title' => [
          '#markup' => $this->t('Search results'),
        ],
        '#no_of_results' => [
          '#markup' => $this->formatPlural($result->getResultCount(), '1 result found', '@count results found'),
        ],
        '#results' => $results,
        '#pager' => [
          '#type' => 'pager',
        ],
      ];

      // Build pager.
      pager_default_initialize($result->getResultCount(), $limit);
    }
    else {
      $build['#no_results_found'] = [
        '#markup' => $this->t('Your search yielded no results.'),
      ];

      $build['#search_help'] = [
        '#markup' => $this->t('<ul>
<li>Check if your spelling is correct.</li>
<li>Remove quotes around phrases to search for each word individually. <em>bike shed</em> will often show more results than <em>&quot;bike shed&quot;</em>.</li>
<li>Consider loosening your query with <em>OR</em>. <em>bike OR shed</em> will often show more results than <em>bike shed</em>.</li>
</ul>'),
      ];
    }

    return $build;
  }

  /**
   * Applies query presets configured in the field instance.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The search query object.
   * @param string $presets
   *   The presets string.
   */
  protected function applyPresets(QueryInterface $query, $presets) {
    $list = explode("\n", $presets);
    $list = array_map('trim', $list);
    $list = array_filter($list, 'strlen');

    foreach ($list as $line) {
      $matches = [];
      // The format of each line can be:
      // - property|value
      // - property|value|operator
      // Property is the name of the Solr field we want to use to filter.
      // Operator is optional.
      if (preg_match('/([^\|]*)\|([^\|]*)(?:\|(.*))?/', $line, $matches)) {
        $field = trim($matches[1]);
        $value = trim($matches[2]);
        $operator = !empty($matches[3]) ? trim($matches[3]) : '=';

        // Handle the IN operator: in this case the value can be a
        // comma-separated list.
        if ($operator === 'IN') {
          $value = explode(',', $value);
          $value = array_map('trim', $value);
        }

        $query->addCondition($field, $value, $operator);
      }
    }
  }

}
