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
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
   * The IDs of the SPDX licences to be compared.
   *
   * @var array
   */
  protected $spdxIds = [];

  /**
   * The cacheable metadata to be applied to the build.
   *
   * @var \Drupal\Core\Cache\CacheableMetadata
   */
  protected $cacheMetadata;

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
   * Respond to a request made to 'joinup_licence.comparer' route.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   A render array.
   *
   * @throws \Http\Discovery\Exception\NotFoundException
   *   When the passed SPDX IDs is not valid.
   */
  public function compare(Request $request): array {
    // Ensure that valid licence IDs were passed along with the request.
    $licence_ids = $this->validatePassedLicences($request->query);

    // Build the raw data structure.
    $data = $this->getComparisionData($licence_ids);

    // Populate the table rows.
    $rows = [];
    foreach ($data as $legal_type_category => $terms) {
      // This header repeats on each top-level term of legal types.
      $rows[] = $this->buildHeader($legal_type_category);
      foreach ($terms as $label => $items) {
        $rows[] = $this->buildRow($legal_type_category, $label, $items);
      }
    }

    $build = [];

    // Add the 'back to filter' link.
    $jla_filter = $this->entityRepository->loadEntityByUuid('node', '3bee8b04-75fd-46a8-94b3-af0d8f5a4c41');
    if ($jla_filter) {
      $build[] = [
        '#type' => 'link',
        '#title' => $this->t('Back to licence filter'),
        '#url' => Url::fromRoute('entity.node.canonical', [
          'node' => $jla_filter->id(),
        ]),
        '#attributes' => [
          'class' => ['licence-back'],
        ],
      ];
    }

    $build[] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#attributes' => [
        'data-drupal-selector' => 'licence-comparer',
      ],
    ];

    $this->cacheMetadata
      // This page cache is properly tagged with cache tags and will be
      // invalidated as soon as one of the dependencies are updated or deleted.
      // However, the licence comparer permits a huge amount of licence
      // combinations and that would flood the cache backend. As updating or
      // deleting licences is a very rare event, the cached items may be stored
      // for a long period of time. We ensure a life time for cached licence
      // comparision of one month: 60 * 60 * 24 * 30 = 2,592,000.
      ->setCacheMaxAge(2592000)
      ->applyTo($build);

    return $build;
  }

  /**
   * Validates the passed SPDX IDs and returns the corresponding Joinup IDs.
   *
   * @param \Symfony\Component\HttpFoundation\ParameterBag $query
   *   The request query parameter bag.
   *
   * @return array
   *   A list of Joinup licence IDs.
   */
  protected function validatePassedLicences(ParameterBag $query): array {
    // Need at least two but no more than static::ROW_COUNT SPDX IDs to be
    // passed along the request, in order to have a comparision.
    if (!$query->has('licence') || !($this->spdxIds = $query->get('licence')) || !is_array($this->spdxIds) || count($this->spdxIds) < 2 || count($this->spdxIds) > static::COLUMN_COUNT) {
      throw new NotFoundHttpException();
    }

    array_walk($this->spdxIds, function (string &$spdx_id): void {
      // If the plus character "+" has been passed in the query string param,
      // it was already converted into space. Revert it.
      $spdx_id = str_replace(' ', '+', $spdx_id);
    });

    // Do a regexp validation of SPDX IDs before checking the backend. The SPDX
    // ID maps to http://spdx.org/rdf/terms#licenseId and it's a unique string
    // containing letters, numbers, ".", "-" or "+".".
    // @see https://spdx.org/rdf/terms/dataproperties/licenseId___-500276407.html
    $pattern = '/^[a-zA-Z0-9][a-zA-Z0-9.+-]+$/';
    array_walk($this->spdxIds, function (string $spdx_id) use ($pattern): void {
      if (!preg_match($pattern, $spdx_id)) {
        throw new NotFoundHttpException();
      }
    });

    $actual_spdx_uris = $this->getRdfStorage()->getQuery()
      ->condition('rid', 'spdx_licence')
      ->condition('field_spdx_licence_id', $this->spdxIds, 'IN')
      ->execute();

    // Some of passed SPDX IDs were not retrieved from the database. This is
    // just another 'page not found'.
    if (count($this->spdxIds) > count($actual_spdx_uris)) {
      throw new NotFoundHttpException();
    }

    $actual_licence_ids = $this->getRdfStorage()->getQuery()
      ->condition('rid', 'licence')
      ->condition('field_licence_spdx_licence', $actual_spdx_uris, 'IN')
      ->execute();

    // Some of passed SPDX IDs don't have a related Joinup licence.
    if (count($this->spdxIds) > count($actual_licence_ids)) {
      throw new NotFoundHttpException();
    }

    return $actual_licence_ids;
  }

  /**
   * Normalizes and returns the data to be compared.
   *
   * As this method iterates over all licences, is used also to gather the
   * cacheable metadata from entities.
   *
   * @param array $licence_ids
   *   The list of Joinup licence IDs to be compared.
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
  protected function getComparisionData(array $licence_ids): array {
    $this->cacheMetadata = new CacheableMetadata();

    $legal_types = $this->getLegalTypeStructure();
    $licence_data = [];
    foreach ($this->getRdfStorage()->loadMultiple($licence_ids) as $licence) {
      /** @var \Drupal\rdf_entity\RdfInterface $spdx_licence */
      $spdx_licence = $licence->field_licence_spdx_licence->entity;

      // Add both, Joinup and SPDX licences as cache dependencies.
      $this->cacheMetadata
        ->addCacheableDependency($licence)
        ->addCacheableDependency($spdx_licence);

      $licence_data[$spdx_licence->get('field_spdx_licence_id')->value] = $licence;
    }

    $data = [];
    foreach ($legal_types as $parent_label => $terms) {
      $data[$parent_label] = [];
      foreach ($terms as $tid => $label) {
        $data[$parent_label][$label] = [];
        foreach ($licence_data as $spdx_id => $licence) {
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
          'licence-sidebar',
          'licence-header',
          'licence-filter--' . strtolower($category),
        ],
      ],
    ];

    foreach ($this->spdxIds as $spdx_id) {
      $row[] = [
        'data' => $spdx_id,
        'class' => [
          'licence-header',
        ],
      ];
    }

    $this->padWithEmptyCells($row, ['licence-header', 'licence-empty']);

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
          'licence-sidebar',
          'licence-cell',
          'licence-filter--' . strtolower($category),
        ],
      ],
    ];

    foreach ($items as $enabled) {
      $row[] = [
        'data' => $enabled ? 'x' : '',
        'class' => [
          'licence-cell',
          'licence-cell-' . ($enabled ? 'on' : 'off'),
        ],
      ];
    }

    $this->padWithEmptyCells($row, ['licence-cell', 'licence-empty']);

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
    $amount = static::COLUMN_COUNT - count($this->spdxIds);
  protected function padWithEmptyCells(array &$row, array $class): void {
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
