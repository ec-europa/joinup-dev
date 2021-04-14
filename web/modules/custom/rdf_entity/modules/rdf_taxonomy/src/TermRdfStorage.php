<?php

declare(strict_types = 1);

namespace Drupal\rdf_taxonomy;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\sparql_entity_storage\Driver\Database\sparql\ConnectionInterface;
use Drupal\sparql_entity_storage\Entity\SparqlMapping;
use Drupal\sparql_entity_storage\SparqlEntityStorage;
use Drupal\sparql_entity_storage\SparqlEntityStorageEntityIdPluginManager;
use Drupal\sparql_entity_storage\SparqlEntityStorageFieldHandlerInterface;
use Drupal\sparql_entity_storage\SparqlEntityStorageGraphHandlerInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\TermStorageInterface;
use Drupal\taxonomy\VocabularyInterface;
use EasyRdf\Graph;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a Controller class for taxonomy terms.
 */
class TermRdfStorage extends SparqlEntityStorage implements TermStorageInterface {

  /**
   * The active database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Bundle predicate array.
   *
   * SKOS has two predicates used on concepts to point to their vocabulary.
   * this depends on their level in the hierarchy.
   *
   * @var array
   */
  protected $bundlePredicate = [
    'http://www.w3.org/2004/02/skos/core#inScheme',
    'http://www.w3.org/2004/02/skos/core#topConceptOf',
  ];

  /**
   * {@inheritdoc}
   */
  protected $rdfBundlePredicate = 'http://www.w3.org/2004/02/skos/core#inScheme';

  /**
   * Array of loaded parents keyed by child term ID.
   *
   * @var array
   */
  protected $parents = [];

  /**
   * Array of all loaded term ancestry keyed by ancestor term ID.
   *
   * @var array
   */
  protected $parentsAll = [];

  /**
   * Array of child terms keyed by parent term ID.
   *
   * @var array
   */
  protected $children = [];

  /**
   * Array of term parents keyed by vocabulary ID and child term ID.
   *
   * @var array
   */
  protected $treeParents = [];

  /**
   * Array of term ancestors keyed by vocabulary ID and parent term ID.
   *
   * @var array
   */
  protected $treeChildren = [];

  /**
   * Array of terms in a tree keyed by vocabulary ID and term ID.
   *
   * @var array
   */
  protected $treeTerms = [];

  /**
   * Array of loaded trees keyed by a cache id matching tree arguments.
   *
   * @var array
   */
  protected $trees = [];

  /**
   * Ancestor entities.
   *
   * @var \Drupal\taxonomy\TermInterface[][]
   */
  protected $ancestors;

  /**
   * Constructs a new term storage instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type this storage is about.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to be used.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface|null $memory_cache
   *   The memory cache backend.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\sparql_entity_storage\Driver\Database\sparql\ConnectionInterface $sparql
   *   The connection object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\sparql_entity_storage\SparqlEntityStorageGraphHandlerInterface $sparql_graph_handler
   *   The sPARQL graph helper service.
   * @param \Drupal\sparql_entity_storage\SparqlEntityStorageFieldHandlerInterface $sparql_field_handler
   *   The SPARQL field mapping service.
   * @param \Drupal\sparql_entity_storage\SparqlEntityStorageEntityIdPluginManager $entity_id_plugin_manager
   *   The entity ID generator plugin manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The active database connection.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityFieldManagerInterface $entity_field_manager,
    CacheBackendInterface $cache,
    MemoryCacheInterface $memory_cache,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    ConnectionInterface $sparql,
    EntityTypeManagerInterface $entity_type_manager,
    LanguageManagerInterface $language_manager,
    ModuleHandlerInterface $module_handler,
    SparqlEntityStorageGraphHandlerInterface $sparql_graph_handler,
    SparqlEntityStorageFieldHandlerInterface $sparql_field_handler,
    SparqlEntityStorageEntityIdPluginManager $entity_id_plugin_manager,
    Connection $database
  ) {
    parent::__construct(
      $entity_type,
      $entity_field_manager,
      $cache,
      $memory_cache,
      $entity_type_bundle_info,
      $sparql,
      $entity_type_manager,
      $language_manager,
      $module_handler,
      $sparql_graph_handler,
      $sparql_field_handler,
      $entity_id_plugin_manager
    );
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): SparqlEntityStorage {
    return new static(
      $entity_type,
      $container->get('entity_field.manager'),
      $container->get('cache.entity'),
      $container->get('entity.memory_cache'),
      $container->get('entity_type.bundle.info'),
      $container->get('sparql.endpoint'),
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('module_handler'),
      $container->get('sparql.graph_handler'),
      $container->get('sparql.field_handler'),
      $container->get('plugin.manager.sparql_entity_id'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function alterGraph(Graph &$graph, EntityInterface $entity): void {
    parent::alterGraph($graph, $entity);
    // @todo Document this. I have no idea what this is for, I only know that
    //   taxonomy terms require this.
    $graph->addResource($entity->id(), 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type', 'http://www.w3.org/2004/02/skos/core#Concept');

    // Remove reference to root. A taxonomy term with no reference to a parent
    // means that is under root.
    $rdf_php = $graph->toRdfPhp();
    foreach ($rdf_php as $resource_uri => $properties) {
      foreach ($properties as $property => $values) {
        // Check only for parent field.
        if ($resource_uri === $entity->id() && $property === 'http://www.w3.org/2004/02/skos/core#broaderTransitive') {
          foreach ($values as $delta => $value) {
            if ($value['value'] === '0') {
              unset($rdf_php[$resource_uri][$property][$delta]);
              break 2;
            }
          }
        }
      }
    }
    // Recreate the graph with new data.
    $graph = new Graph($graph->getUri(), $rdf_php);
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(?array $ids = NULL, ?array $graph_ids = NULL): void {
    drupal_static_reset('taxonomy_term_count_nodes');
    $this->parents = [];
    $this->parentsAll = [];
    $this->children = [];
    $this->treeChildren = [];
    $this->treeParents = [];
    $this->treeTerms = [];
    $this->trees = [];
    parent::resetCache($ids, $graph_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteTermHierarchy($tids) {}

  /**
   * {@inheritdoc}
   */
  public function updateTermHierarchy(EntityInterface $term) {}

  /**
   * {@inheritdoc}
   */
  public function loadParents($tid) {
    $terms = [];
    /** @var \Drupal\taxonomy\TermInterface $term */
    if ($tid && $term = $this->load($tid)) {
      foreach ($this->getParents($term) as $id => $parent) {
        // This method currently doesn't return the <root> parent.
        // @see https://www.drupal.org/node/2019905
        if (!empty($id)) {
          $terms[$id] = $parent;
        }
      }
    }

    return $terms;
  }

  /**
   * Returns a list of parents of this term.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   The parent taxonomy term entities keyed by term ID. If this term has a
   *   <root> parent, that item is keyed with 0 and will have NULL as value.
   */
  protected function getParents(TermInterface $term) {
    $parent = $term->get('parent');
    if ($parent->isEmpty()) {
      return [0 => NULL];
    }

    $ids = [];
    foreach ($term->get('parent') as $item) {
      $ids[] = $item->target_id;
    }

    if ($ids) {
      $query = $this->getQuery()->condition('tid', $ids, 'IN');
      return static::loadMultiple($query->execute());
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function loadAllParents($tid) {
    /** @var \Drupal\taxonomy\TermInterface $term */
    return (!empty($tid) && $term = $this->load($tid)) ? $this->getAncestors($term) : [];
  }

  /**
   * Returns all ancestors of this term.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   A list of ancestor taxonomy term entities keyed by term ID.
   *
   * @internal
   * @todo Refactor away when TreeInterface is introduced.
   */
  protected function getAncestors(TermInterface $term) {
    if (!isset($this->ancestors[$term->id()])) {
      $this->ancestors[$term->id()] = [$term->id() => $term];
      $search[] = $term->id();

      while ($tid = array_shift($search)) {
        foreach ($this->getParents(static::load($tid)) as $id => $parent) {
          if ($parent && !isset($this->ancestors[$term->id()][$id])) {
            $this->ancestors[$term->id()][$id] = $parent;
            $search[] = $id;
          }
        }
      }
    }
    return $this->ancestors[$term->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function loadChildren($tid, $vid = NULL) {
    /** @var \Drupal\taxonomy\TermInterface $term */
    return (!empty($tid) && $term = $this->load($tid)) ? $this->getChildren($term) : [];
  }

  /**
   * Returns all children terms of this term.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   A list of children taxonomy term entities keyed by term ID.
   *
   * @internal
   * @todo Refactor away when TreeInterface is introduced.
   */
  public function getChildren(TermInterface $term) {
    $query = $this->getQuery()->condition('parent', $term->id());
    return static::loadMultiple($query->execute());
  }

  /**
   * {@inheritdoc}
   */
  public function loadTree($vid, $parent = 0, $max_depth = NULL, $load_entities = FALSE) {
    // The parent is either the root (0 or '') or a non-empty value. If NULL has
    // been passed, means that tree under a non-saved term was requested but
    // a non-saved term cannot have children.
    if ($parent === NULL) {
      return [];
    }
    // Core term uses 0 (as integer) for root level. RDF Taxonomy has string IDs
    // thus we convert 0 to '' (empty string) to denote the root level.
    $parent = $parent === 0 ? '' : $parent;

    $cache_key = implode(':', func_get_args());
    if (empty($this->trees[$cache_key])) {
      // We cache trees, so it's not CPU-intensive to call on a term and its
      // children, too.
      if (empty($this->treeChildren[$vid])) {
        $mapping = SparqlMapping::loadByName('taxonomy_term', $vid);
        $concept_schema = $mapping->getRdfType();
        $this->treeChildren[$vid] = [];
        $this->treeParents[$vid] = [];
        $this->treeTerms[$vid] = [];

        $select = 'SELECT DISTINCT ?tid ?label ?parent';
        $weight_where = '';
        $order_by = 'STR(?label)';
        if ($mapping->isMapped('weight')) {
          $select .= ' ?weight';
          $weight_where = "OPTIONAL { ?tid <{$mapping->getMapping('weight')['predicate']}> ?weight } .";
          $order_by = '?weight, ' . $order_by;
        }

        $query = <<<QUERY
{$select}
WHERE {
  ?tid ?relation <$concept_schema> .
  ?tid <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/2004/02/skos/core#Concept> .
  ?tid <http://www.w3.org/2004/02/skos/core#prefLabel> ?label .
  FILTER (?relation IN (<http://www.w3.org/2004/02/skos/core#inScheme>, <http://www.w3.org/2004/02/skos/core#topConceptOf>) ) .
  FILTER (lang(?label) = 'en') .
  OPTIONAL {?tid <http://www.w3.org/2004/02/skos/core#broaderTransitive> ?parent } .
  {$weight_where}
}
ORDER BY {$order_by}
QUERY;
        $result = $this->sparql->query($query);
        foreach ($result as $term_res) {
          $term_parent = isset($term_res->parent) ? (string) $term_res->parent : '';
          $term = (object) [
            'tid' => (string) $term_res->tid,
            'vid' => $vid,
            'name' => (string) $term_res->label,
            'parent' => $term_parent,
            'weight' => $term_res->weight ?? 0,
          ];
          $this->treeChildren[$vid][$term_parent][] = $term->tid;
          $this->treeParents[$vid][$term->tid][] = $term_parent;
          $this->treeTerms[$vid][$term->tid] = $term;
        }
      }

      // Load full entities, if necessary. The entity controller statically
      // caches the results.
      $term_entities = [];
      if ($load_entities) {
        $term_entities = $this->loadMultiple(array_keys($this->treeTerms[$vid]));
      }

      $max_depth = (!isset($max_depth)) ? count($this->treeChildren[$vid]) : $max_depth;
      $tree = [];

      // Keeps track of the parents we have to process, the last entry is used
      // for the next processing step.
      $process_parents = [];
      $process_parents[] = $parent;
      // Loops over the parent terms and adds its children to the tree array.
      // Uses a loop instead of a recursion, because it's more efficient.
      while (count($process_parents)) {
        $parent = array_pop($process_parents);
        // The number of parents determines the current depth.
        $depth = count($process_parents);
        if ($max_depth > $depth && !empty($this->treeChildren[$vid][$parent])) {
          $has_children = FALSE;
          $child = current($this->treeChildren[$vid][$parent]);
          do {
            if (empty($child)) {
              break;
            }
            $term = $load_entities ? $term_entities[$child] : $this->treeTerms[$vid][$child];
            if (isset($this->treeParents[$vid][$load_entities ? $term->id() : $term->tid])) {
              // Clone the term so that the depth attribute remains correct
              // in the event of multiple parents.
              $term = clone $term;
            }
            $term->depth = $depth;
            unset($term->parent);
            $tid = $load_entities ? $term->id() : $term->tid;
            $term->parents = $this->treeParents[$vid][$tid];
            $tree[] = $term;
            if (!empty($this->treeChildren[$vid][$tid])) {
              $has_children = TRUE;

              // We have to continue with this parent later.
              $process_parents[] = $parent;
              // Use the current term as parent for the next iteration.
              $process_parents[] = $tid;

              // Reset pointers for child lists because we step in there more
              // often with multi parents.
              reset($this->treeChildren[$vid][$tid]);
              // Move pointer so that we get the correct term the next time.
              next($this->treeChildren[$vid][$parent]);
              break;
            }
          } while ($child = next($this->treeChildren[$vid][$parent]));

          if (!$has_children) {
            // We processed all terms in this hierarchy-level, reset pointer
            // so that this function works the next time it gets called.
            reset($this->treeChildren[$vid][$parent]);
          }
        }
      }
      $this->trees[$cache_key] = $tree;
    }
    return $this->trees[$cache_key];
  }

  /**
   * {@inheritdoc}
   */
  public function nodeCount($vid) {
    // @todo Is this possible to determine?
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function resetWeights($vid) {}

  /**
   * {@inheritdoc}
   */
  public function getNodeTerms(array $nids, array $vocabs = [], $langcode = NULL) {
    // @todo Test this.
    $query = $this->database->select('taxonomy_index', 'tn');
    $query->fields('tn', ['tid']);
    $query->addField('tn', 'nid', 'node_nid');
    $query->condition('tn.nid', $nids, 'IN');

    $results = [];
    $all_tids = [];
    foreach ($query->execute() as $term_record) {
      $results[$term_record->node_nid][] = $term_record->tid;
      $all_tids[] = $term_record->tid;
    }

    $all_terms = $this->loadMultiple($all_tids);
    $terms = [];
    foreach ($results as $nid => $tids) {
      foreach ($tids as $tid) {
        $terms[$nid][$tid] = $all_terms[$tid];
      }
    }
    return $terms;
  }

  /**
   * {@inheritdoc}
   */
  public function getVocabularyHierarchyType($vid) {
    return VocabularyInterface::HIERARCHY_SINGLE;
  }

  /**
   * {@inheritdoc}
   */
  public function getTermIdsWithPendingRevisions() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    $vars = parent::__sleep();
    // Do not serialize static cache.
    unset($vars['parents'], $vars['parentsAll'], $vars['children'], $vars['treeChildren'], $vars['treeParents'], $vars['treeTerms'], $vars['trees']);
    return $vars;
  }

  /**
   * {@inheritdoc}
   */
  public function __wakeup() {
    parent::__wakeup();
    // Initialize static caches.
    $this->parents = [];
    $this->parentsAll = [];
    $this->children = [];
    $this->treeChildren = [];
    $this->treeParents = [];
    $this->treeTerms = [];
    $this->trees = [];
  }

}
