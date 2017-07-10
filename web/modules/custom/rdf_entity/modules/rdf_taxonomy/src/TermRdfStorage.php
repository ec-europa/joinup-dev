<?php

namespace Drupal\rdf_taxonomy;

use Drupal\Core\Entity\EntityInterface;
use Drupal\rdf_entity\Entity\RdfEntitySparqlStorage;
use Drupal\taxonomy\TermStorageInterface;
use EasyRdf\Graph;

/**
 * Defines a Controller class for taxonomy terms.
 */
class TermRdfStorage extends RdfEntitySparqlStorage implements TermStorageInterface {

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
   * {@inheritdoc}
   */
  protected function doPreSave(EntityInterface $entity) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $parent = $entity->get('parent');
    if ($parent->first() && empty($parent->target_id)) {
      // If the parent target ID is set to '' (empty string), remove the item to
      // avoid storing a triple corresponding to parent field in the backend.
      $parent->removeItem(0);
    }
    return parent::doPreSave($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function alterGraph(Graph &$graph, EntityInterface $entity) {
    parent::alterGraph($graph, $entity);
    // @todo Document this. I have no idea what this is for, I only know that
    //   taxonomy terms require this.
    $graph->addResource($entity->id(), 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type', 'http://www.w3.org/2004/02/skos/core#Concept');
  }

  /**
   * {@inheritdoc}
   */
  public function create(array $values = []) {
    // Save new terms with no parents by default.
    if (empty($values['parent'])) {
      $values['parent'] = [''];
    }
    $entity = parent::create($values);
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $ids = NULL) {
    drupal_static_reset('taxonomy_term_count_nodes');
    $this->parents = [];
    $this->parentsAll = [];
    $this->children = [];
    $this->treeChildren = [];
    $this->treeParents = [];
    $this->treeTerms = [];
    $this->trees = [];
    parent::resetCache($ids);
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
    if (empty($this->parents[$tid])) {
      $parents = [];
      $ids = [];
      $query = <<<QUERY
SELECT ?parents
WHERE {
  <$tid> <http://www.w3.org/2004/02/skos/core#broaderTransitive> ?parents
}
QUERY;
      $result = $this->sparql->query($query);
      foreach ($result as $item) {
        $ids[] = (string) $item->parents;
      }
      if ($ids) {
        $parents = $this->loadMultiple($ids);
      }
      $this->parents[$tid] = $parents;
    }
    return $this->parents[$tid];
  }

  /**
   * {@inheritdoc}
   */
  public function loadAllParents($tid) {
    if (!isset($this->parentsAll[$tid])) {
      $parents = [];
      if ($term = $this->load($tid)) {
        $parents[$term->id()] = $term;
        $terms_to_search[] = $term->id();

        while ($tid = array_shift($terms_to_search)) {
          if ($new_parents = $this->loadParents($tid)) {
            foreach ($new_parents as $new_parent) {
              if (!isset($parents[$new_parent->id()])) {
                $parents[$new_parent->id()] = $new_parent;
                $terms_to_search[] = $new_parent->id();
              }
            }
          }
        }
      }

      $this->parentsAll[$tid] = $parents;
    }
    return $this->parentsAll[$tid];
  }

  /**
   * {@inheritdoc}
   */
  public function loadChildren($tid, $vid = NULL) {
    if (!isset($this->children[$tid])) {
      $children = [];
      // Get terms whom refer to tid as being a parent.
      $query = <<<QUERY
SELECT ?children
WHERE {
   ?children <http://www.w3.org/2004/02/skos/core#broaderTransitive> <$tid>
}
QUERY;
      $result = $this->sparql->query($query);
      $ids = [];
      foreach ($result as $item) {
        $ids[] = (string) $item->children;
      }
      if ($ids) {
        $children = $this->loadMultiple($ids);
      }
      $this->children[$tid] = $children;
    }
    return $this->children[$tid];
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
        /** @var \Drupal\taxonomy\Entity\Vocabulary $voc */
        $voc = entity_load('taxonomy_vocabulary', $vid);
        $concept_schema = $voc->getThirdPartySetting('rdf_entity', 'rdf_type');
        $this->treeChildren[$vid] = [];
        $this->treeParents[$vid] = [];
        $this->treeTerms[$vid] = [];
        $query = <<<QUERY
SELECT DISTINCT ?tid ?label ?parent
WHERE {
  ?tid ?relation <$concept_schema> .
  ?tid <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/2004/02/skos/core#Concept> .
  ?tid <http://www.w3.org/2004/02/skos/core#prefLabel> ?label .
  FILTER (?relation IN (<http://www.w3.org/2004/02/skos/core#inScheme>, <http://www.w3.org/2004/02/skos/core#topConceptOf>) ) .
  FILTER (lang(?label) = 'en') .
  OPTIONAL {?tid <http://www.w3.org/2004/02/skos/core#broaderTransitive> ?parent }
}
ORDER BY (STR(?label))
QUERY;
        $result = $this->sparql->query($query);
        foreach ($result as $term_res) {
          $term_parent = isset($term_res->parent) ? (string) $term_res->parent : '';
          $term = (object) [
            'tid' => (string) $term_res->tid,
            'vid' => $vid,
            'name' => (string) $term_res->label,
            'parent' => $term_parent,
            'weight' => 0,
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
    $query = db_select('taxonomy_index', 'tn');
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
