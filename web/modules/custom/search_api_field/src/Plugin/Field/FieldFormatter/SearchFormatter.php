<?php

namespace Drupal\search_api_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\ParseMode\ParseModePluginManager;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\SearchApiException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Plugin implementation of the 'link' formatter.
 *
 * @FieldFormatter(
 *   id = "search",
 *   label = @Translation("Search"),
 *   field_types = {
 *     "search"
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
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $settings = $this->getSettings();

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $entity = $items->getEntity();
    $field_definition = $this->fieldDefinition;

    // Avoid infinite recursion when a search node is shown as a result.
    if ($entity->do_not_recurse) {
      return [];
    }

    $index_id = $field_definition->getSetting('index');
    /* @var $search_api_index \Drupal\search_api\IndexInterface */
    $search_api_index = Index::load($index_id);

    if (empty($search_api_index)) {
      throw new SearchApiException("Could not load index with ID '$index_id'.");
    }

    // At the moment, this formatter supports only single-value fields.
    $settings = $items->first()->value;

    $limit = !empty($settings['limit']) ? $settings['limit'] : 10;

    $options = [
      'limit' => $limit,
      'offset' => !is_null($this->request->get('page')) ? $this->request->get('page') * $limit : 0,
      'search id' => 'search_api_field:' . $field_definition->getTargetEntityTypeId() . '.' . $field_definition->getName(),
    ];
    $query = $search_api_index->query($options);

    $query->setParseMode($this->parseModeManager->createInstance('direct'));

    if (!empty($settings['query_presets'])) {
      $this->applyPresets($query, $settings['query_presets']);
    }

    $result = $query->execute();
    $result_items = $result->getResultItems();

    $results = array();
    /* @var $item \Drupal\search_api\Item\ItemInterface */
    foreach ($result_items as $item) {
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $item->getOriginalObject()->getValue();
      if (!$entity) {
        continue;
      }
      $entity->do_not_recurse = TRUE;

      // Render as view modes.
      $key = 'entity:' . $entity->getEntityTypeId() . '_' . $entity->bundle();
      // @todo $search_api_page->getViewModeConfiguration();
      $view_mode_configuration = [];
      $view_mode = isset($view_mode_configuration[$key]) ? $view_mode_configuration[$key] : 'default';
      $results[] = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId())->view($entity, $view_mode);
    }

    if (!empty($results)) {

      $build['#search_title'] = array(
        '#markup' => $this->t('Search results'),
      );

      $build['#no_of_results'] = array(
        '#markup' => $this->formatPlural($result->getResultCount(), '1 result found', '@count results found'),
      );

      $build['#results'] = $results;

      // Build pager.
      pager_default_initialize($result->getResultCount(), $limit);
      $build['#pager'] = array(
        '#type' => 'pager',
      );
    }
    else {
      $build['#no_results_found'] = array(
        '#markup' => $this->t('Your search yielded no results.'),
      );

      $build['#search_help'] = array(
        '#markup' => $this->t('<ul>
<li>Check if your spelling is correct.</li>
<li>Remove quotes around phrases to search for each word individually. <em>bike shed</em> will often show more results than <em>&quot;bike shed&quot;</em>.</li>
<li>Consider loosening your query with <em>OR</em>. <em>bike OR shed</em> will often show more results than <em>bike shed</em>.</li>
</ul>'),
      );
    }

    $results['#cache'] = [
      'max-age' => 0,
    ];

    $build['#theme'] = 'search_api_field';

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
      if (preg_match('/([^\|]*)\|([^\|]*)(?:\|(.*))?/', $line, $matches)) {
        $field = trim($matches[1]);
        $value = trim($matches[2]);
        $operator = !empty($matches[3]) ? trim($matches[3]) : '=';

        if ($operator === 'IN') {
          $value = explode(',', $value);
          $value = array_map('trim', $value);
        }

        $query->addCondition($field, $value, $operator);
      }
    }
  }

}
