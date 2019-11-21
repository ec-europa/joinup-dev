<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Url;
use Drupal\joinup_licence\LicenceComparerHelper;
use Drupal\sparql_entity_storage\SparqlEntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a page controller callbacks.
 */
class LicenceComparerController extends ControllerBase {

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The RDF entity storage.
   *
   * @var \Drupal\sparql_entity_storage\SparqlEntityStorageInterface
   */
  protected $rdfStorage;

  /**
   * An ordered list of Joinup licence entities keyed by their SPDX ID.
   *
   * @var \Drupal\rdf_entity\RdfInterface[]
   */
  protected $licences = [];

  /**
   * Constructs a new controller instance.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   */
  public function __construct(EntityRepositoryInterface $entity_repository) {
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static($container->get('entity.repository'));
  }

  /**
   * Responds to a request made to 'joinup_licence.comparer' route.
   *
   * @param \Drupal\rdf_entity\RdfInterface[] $licences
   *   An ordered list of Joinup licence entities keyed by their SPDX ID.
   *
   * @return array
   *   A render array.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
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

    // Collect cache metadata from dependencies.
    $cache_metadata = new CacheableMetadata();
    foreach ($this->licences as $licence) {
      $cache_metadata
        ->addCacheableDependency($licence)
        ->addCacheableDependency($licence->field_licence_spdx_licence->entity);
    }

    $build = [];

    // Add the 'back to filter' link.
    $jla_filter = $this->entityRepository->loadEntityByUuid('node', '3bee8b04-75fd-46a8-94b3-af0d8f5a4c41');

    if ($jla_filter) {
      $build[] = [
        '#type' => 'link',
        '#title' => ['#markup' => '<span class="icon icon--previous"></span> ' . $this->t('Back to licence filter')],
        '#url' => Url::fromRoute('entity.node.canonical', [
          'node' => $jla_filter->id(),
        ]),
        '#attributes' => [
          'class' => ['licence-comparer__back'],
        ],
      ];
    }

    $build[] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#attributes' => [
        'data-drupal-selector' => 'licence-comparer',
        'class' => ['licence-comparer'],
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
      ];
    }

    $this->padWithEmptyCells($row, ['licence-comparer__header', 'licence-comparer__empty']);

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

    $checked_markup = '<span class="icon icon--check-2"></span><span class="visually-hidden">x</span>';
    foreach ($items as $enabled) {
      $row[] = [
        'data' => $enabled ? ['#markup' => $checked_markup] : '',
        'class' => [
          'licence-comparer__cell',
          'licence-comparer__cell-' . ($enabled ? 'on' : 'off'),
        ],
      ];
    }

    $this->padWithEmptyCells($row, [
      'licence-comparer__cell',
      'licence-comparer__empty',
      'licence-comparer__empty-cell',
    ]);

    return $row;
  }

  /**
   * Pads the given row with empty cells until reaches static::ROW_COUNT cells.
   *
   * @param array $row
   *   A row array.
   * @param array $class
   *   A list of classes to be added to the empty cell.
   */
  protected function padWithEmptyCells(array &$row, array $class): void {
    $amount = LicenceComparerHelper::MAX_LICENCE_COUNT - count($this->licences);
    for ($i = 0; $i < $amount; $i++) {
      $row[] = [
        'data' => '',
        'class' => $class,
      ];
    }
  }

  /**
   * Returns the RDF entity storage.
   *
   * @return \Drupal\sparql_entity_storage\SparqlEntityStorageInterface
   *   The RDF entity storage.
   */
  protected function getRdfStorage(): SparqlEntityStorageInterface {
    if (!isset($this->rdfStorage)) {
      $this->rdfStorage = $this->entityTypeManager()->getStorage('rdf_entity');
    }
    return $this->rdfStorage;
  }

}
