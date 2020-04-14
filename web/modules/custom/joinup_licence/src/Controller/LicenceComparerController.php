<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\joinup_licence\LicenceComparerHelper;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a page controller callbacks.
 */
class LicenceComparerController extends ControllerBase {

  /**
   * An ordered list of Joinup licence entities keyed by their SPDX ID.
   *
   * @var \Drupal\rdf_entity\RdfInterface[]
   */
  protected $licences = [];

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }

  /**
   * Responds to a request made to 'joinup_licence.comparer' route.
   *
   * @param \Drupal\rdf_entity\RdfInterface[] $licences
   *   An ordered list of Joinup licence entities keyed by their SPDX ID.
   *
   * @return array
   *   A render array.
   */
  public function compare(array $licences): array {
    $this->licences = $licences;

    // Build the raw data structure.
    $data = $this->getComparisionData();

    // Populate the table rows.
    $rows = [];
    foreach ($data as $legal_type_category => $terms) {
      // This header repeats on each top-level term of legal types.
      $rows[] = $this->buildHeader($legal_type_category);
      foreach ($terms as $label => $items) {
        $rows[] = $this->buildRow($legal_type_category, $label, $items);
      }
    }

    $cache_metadata = new CacheableMetadata();
    $data = [];
    foreach ($this->licences as $spdx_id => $licence) {
      // Collect cache metadata from dependencies.
      $cache_metadata
        ->addCacheableDependency($licence)
        ->addCacheableDependency($licence->field_licence_spdx_licence->entity);

      // Build licence data to be attached as Json to the page.
      $data[$spdx_id] = [
        'title' => $licence->label(),
        'description' => check_markup($licence->field_licence_description->value, 'content_editor'),
        'spdxUrl' => $licence->field_licence_spdx_licence->target_id,
      ];
    }

    $build = [
      [
        '#theme' => 'table',
        '#rows' => $rows,
        '#attributes' => [
          'data-drupal-selector' => 'licence-comparer',
          'class' => ['licence-comparer'],
        ],
      ],
      '#attached' => [
        'html_head' => [
          [
            [
              '#type' => 'html_tag',
              '#tag' => 'script',
              '#value' => Json::encode($data),
              '#attributes' => [
                'type' => 'application/json',
                'data-drupal-selector' => 'licence-comparer-data',
              ],
            ],
            'licence_comparer_data',
          ],
        ],
      ],
    ];

    $cache_metadata
      // This page cache is properly tagged with cache tags and will be
      // invalidated as soon as one of the dependencies are updated or deleted.
      // However, the licence comparer permits a huge amount of licence
      // combinations and that would flood the cache backend. As updating or
      // deleting licences is a very rare event, the cached items may be stored
      // for a long period of time. We ensure a life time for cached licence
      // comparision of two months: 2 * 60s * 60m * 24h * 30d = 5184000s.
      ->setCacheMaxAge(5184000)
      ->applyTo($build);

    return $build;
  }

  /**
   * Normalizes and returns the data to be compared.
   *
   * @return array
   *   An associative array keyed by legal type top-level term label and having
   *   as values an associative array keyed by the legal type second-level
   *   label and values a new level of associative arrays keyed by SPDX ID and
   *   having a boolean as value indicating if this licence conforms to that
   *   specific legal type.
   *   @code
   *   [
   *     'Can' => [
   *       'Use/reproduce' => [
   *         'Apache-2.0' => TRUE,
   *         'CC-BY-NC-SA-4.0' => FALSE,
   *         ...
   *       ],
   *       ...
   *     ],
   *     ...
   *   ]
   *   @endcode
   */
  protected function getComparisionData(): array {
    $legal_types = $this->getLegalTypeStructure();

    $data = [];
    foreach ($legal_types as $parent_label => $terms) {
      $data[$parent_label] = [];
      foreach ($terms as $tid => $label) {
        $data[$parent_label][$label] = [];
        foreach ($this->licences as $spdx_id => $licence) {
          $has_term = FALSE;
          /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $type_item */
          foreach ($licence->get('field_licence_legal_type') as $type_item) {
            if ($type_item->target_id === $tid) {
              $has_term = TRUE;
              break;
            }
          }
          $data[$parent_label][$label][$spdx_id] = $has_term;
        }
      }
    }

    return $data;
  }

  /**
   * Returns the legal type structure.
   *
   * @return array
   *   An associative array keyed by the legal type top-level term label and
   *   having, as values, associative arrays keyed by the legal type
   *   second-level term ID with the tern name as value.
   *   @code
   *   [
   *     'Can' => [
   *       'http://joinup.eu/legal-type#Distribute' => 'Distribute',
   *       'http://joinup.eu/legal-type#Modify-Merge' => 'Modify/merge',
   *       ...
   *     ],
   *     ...
   *   ]
   *   @endcode
   */
  protected function getLegalTypeStructure(): array {
    /** @var \Drupal\rdf_taxonomy\TermRdfStorage $storage */
    $storage = $this->entityTypeManager()->getStorage('taxonomy_term');
    $tree = $storage->loadTree('legal_type');

    // Collect first the top level parents.
    $parents = [];
    foreach ($tree as $term) {
      if ($term->depth === 0) {
        $parents[$term->tid] = $term->name;
      }
    }

    $legal_types = [];
    foreach ($tree as $term) {
      if ($term->depth === 1) {
        $parent_label = $parents[$term->parents[0]];
        $legal_types[$parent_label][$term->tid] = $term->name;
      }
    }

    return $legal_types;
  }

  /**
   * Builds the table header.
   *
   * @param string $category
   *   The legal type top-level term label.
   *
   * @return array
   *   A row array suitable to be used with the 'table' theme.
   */
  protected function buildHeader(string $category): array {
    $row = [
      [
        'data' => $category,
        'class' => [
          'licence-comparer__sidebar-header',
          'licence-filter--' . strtolower($category),
        ],
      ],
    ];

    foreach (array_keys($this->licences) as $spdx_id) {
      $row[] = [
        'data' => $spdx_id,
        'class' => [
          'licence-comparer__header',
        ],
        'data-licence-id' => $spdx_id,
      ];
    }

    $this->fillHeaderRows($row);

    return $row;
  }

  /**
   * Builds the table current row.
   *
   * @param string $category
   *   The legal type top-level term label.
   * @param string $label
   *   The legal type second-level term label.
   * @param bool[] $items
   *   A list of boolean flags keyed the SPDX ID indicating if that licence
   *   conforms to that legal type term.
   *
   * @return array
   *   A row array suitable to be used with the 'table' theme.
   */
  protected function buildRow(string $category, string $label, array $items): array {
    $row = [
      [
        'data' => $label,
        'class' => [
          'licence-comparer__sidebar-cell',
          'licence-filter--' . strtolower($category),
        ],
      ],
    ];

    $checked_markup = '<span class="icon icon--check-2"></span>';
    foreach ($items as $enabled) {
      $row[] = [
        'data' => $enabled ? ['#markup' => $checked_markup] : '',
        'class' => [
          'licence-comparer__cell',
          'licence-comparer__cell-' . ($enabled ? 'on' : 'off'),
        ],
      ];
    }

    $this->fillContentRows($row);

    return $row;
  }

  /**
   * Fills in the header rows with an add licence option and empty cells.
   *
   * @param array $row
   *   A row array.
   */
  protected function fillHeaderRows(array &$row): void {
    $amount = LicenceComparerHelper::MAX_LICENCE_COUNT - count($this->licences);
    if ($amount === 0) {
      return;
    }

    $classes = ['licence-comparer__header', 'licence-comparer__empty'];
    // \Drupal\Core\Render\Element\Select::processSelect does not seem
    // to be called if the element is not rendered through a form
    // builder. Thus, the name of the field and the default values are
    // not set. Manually set the additional properties.
    $row[] = [
      'data' => [
        'licence_search_label' => [
          '#type' => 'label',
          '#for' => 'licence-search',
          '#title' => $this->t('Add licence'),
          '#title_display' => 'invisible',
        ],
        'licence_search' => [
          '#type' => 'select',
          '#options' => $this->getLicenceOptions(),
          '#default_value' => '',
          '#attributes' => [
            'class' => ['auto-submit'],
            'name' => 'licence_search',
            'id' => 'licence-search',
            'title' => $this->t('Add licence'),
          ],
          '#attached' => [
            'library' => [
              'joinup_licence/search_auto_submit',
            ],
          ],
        ],
      ],
      'class' => $classes,
    ];
    $amount--;

    $this->padWithEmptyCells($row, $classes, $amount);
  }

  /**
   * Fills in the content rows with empty cells.
   *
   * @param array $row
   *   A row array.
   */
  protected function fillContentRows(array &$row): void {
    $classes = [
      'licence-comparer__cell',
      'licence-comparer__empty',
      'licence-comparer__empty-cell',
    ];
    $amount = LicenceComparerHelper::MAX_LICENCE_COUNT - count($this->licences);
    $this->padWithEmptyCells($row, $classes, $amount);
  }

  /**
   * Pads the given row with empty cells until reaches static::ROW_COUNT cells.
   *
   * @param array $row
   *   A row array.
   * @param array $class
   *   A list of classes to be added to the empty cell.
   * @param int $amount
   *   The number of empty cells to add.
   */
  protected function padWithEmptyCells(array &$row, array $class, int $amount): void {
    for ($i = 0; $i < $amount; $i++) {
      $row[] = [
        'data' => '',
        'class' => $class,
      ];
    }
  }

  /**
   * Returns list of available licences to add to the compare table.
   *
   * @return array
   *   A list of licence labels indexed by their SPDX ID.
   */
  protected function getLicenceOptions(): array {
    $rdf_storage = $this->entityTypeManager->getStorage('rdf_entity');

    $query = $rdf_storage->getQuery()->condition('rid', 'licence');
    if (!empty($this->licences)) {
      // Do not include licences already in the comparison page if any.
      $existing_ids = array_map(function (RdfInterface $licence): string {
        return $licence->id();
      }, $this->licences);
      $query->condition('id', $existing_ids, 'NOT IN');
    }

    // In any case, do not show licences that are not linked to an SPDX licence.
    $query->exists('field_licence_spdx_licence');
    $options = ['' => $this->t('- Add licence -')];
    $ids = $query->execute();
    foreach ($rdf_storage->loadMultiple($ids) as $licence) {
      $spdx_licence = $licence->get('field_licence_spdx_licence')->entity;
      $options[$spdx_licence->get('field_spdx_licence_id')->value] = $spdx_licence->label() . ' | ' . $licence->label();
    }
    // Query sorting on properties other than ID and rid are not supported in
    // sparql_entity_storage yet.
    // @see: https://github.com/ec-europa/sparql_entity_storage/issues/10
    asort($options);

    return $options;
  }

}
