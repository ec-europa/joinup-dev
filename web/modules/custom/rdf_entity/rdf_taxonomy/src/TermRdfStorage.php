<?php

namespace Drupal\rdf_taxonomy;

use Drupal\Core\Entity\EntityInterface;
use Drupal\rdf_entity\Entity\RdfEntitySparqlStorage;
use Drupal\taxonomy\TermStorageInterface;

/**
 * Defines a Controller class for taxonomy terms.
 */
class TermRdfStorage extends RdfEntitySparqlStorage implements TermStorageInterface {

  /**
   * Array of loaded parents keyed by child term ID.
   *
   * @var array
   */
  protected $parents = array();

  /**
   * Array of all loaded term ancestry keyed by ancestor term ID.
   *
   * @var array
   */
  protected $parentsAll = array();

  /**
   * Array of child terms keyed by parent term ID.
   *
   * @var array
   */
  protected $children = array();

  /**
   * Array of term parents keyed by vocabulary ID and child term ID.
   *
   * @var array
   */
  protected $treeParents = array();

  /**
   * Array of term ancestors keyed by vocabulary ID and parent term ID.
   *
   * @var array
   */
  protected $treeChildren = array();

  /**
   * Array of terms in a tree keyed by vocabulary ID and term ID.
   *
   * @var array
   */
  protected $treeTerms = array();

  /**
   * Array of loaded trees keyed by a cache id matching tree arguments.
   *
   * @var array
   */
  protected $trees = array();

  /**
   * {@inheritdoc}
   *
   * @param array $values
   *   An array of values to set, keyed by property name. A value for the
   *   vocabulary ID ('vid') is required.
   */
  public function create(array $values = array()) {
    // Save new terms with no parents by default.
    if (empty($values['parent'])) {
      $values['parent'] = array(0);
    }
    $entity = parent::create($values);
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $ids = NULL) {
    drupal_static_reset('taxonomy_term_count_nodes');
    $this->parents = array();
    $this->parentsAll = array();
    $this->children = array();
    $this->treeChildren = array();
    $this->treeParents = array();
    $this->treeTerms = array();
    $this->trees = array();
    parent::resetCache($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteTermHierarchy($tids) {
    // @todo Implement hierarchy updates.
  }

  /**
   * {@inheritdoc}
   */
  public function updateTermHierarchy(EntityInterface $term) {
    // @todo Implement hierarchy updates.
  }

  /**
   * {@inheritdoc}
   */
  public function loadParents($tid) {
    if (!isset($this->parents[$tid])) {
      $parents = array();
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
      $parents = array();
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
      $children = array();
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
    $cache_key = implode(':', func_get_args());
    if (!isset($this->trees[$cache_key])) {

      // We cache trees, so it's not CPU-intensive to call on a term and its
      // children, too.
      if (!isset($this->treeChildren[$vid])) {
        /** @var \Drupal\taxonomy\Entity\Vocabulary $voc */
        $voc = entity_load('taxonomy_vocabulary', $vid);
        $concept_schema = $voc->getThirdPartySetting('rdf_entity', 'ConceptScheme');
        $this->treeChildren[$vid] = array();
        $this->treeParents[$vid] = array();
        $this->treeTerms[$vid] = array();
        $query = <<<QUERY
SELECT ?tid ?label ?parent
WHERE {
  ?tid <http://www.w3.org/2004/02/skos/core#inScheme> <$concept_schema> .
  ?tid <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/2004/02/skos/core#Concept> .
  ?tid <http://www.w3.org/2004/02/skos/core#prefLabel> ?label .
  FILTER (lang(?label) = 'en') .
  OPTIONAL {?tid <http://www.w3.org/2004/02/skos/core#broaderTransitive> ?parent }
}
QUERY;
        $result = $this->sparql->query($query);
        foreach ($result as $term_res) {
          $parent = 0;
          if (isset($term_res->parent)) {
            $parent = (string) $term_res->parent;
          }
          $tid = (string) $term_res->tid;
          $label = (string) $term_res->label;
          $values = [
            'tid' => $tid,
            'vid' => $vid,
            'name' => $label,
            'parent' => $parent,
            'weight' => 0,
          ];
          $term = (object) $values;
          $this->treeChildren[$vid][$parent][] = $term->tid;
          $this->treeParents[$vid][$term->tid][] = $parent;
          $this->treeTerms[$vid][$term->tid] = $term;
        }
      }

      // Load full entities, if necessary. The entity controller statically
      // caches the results.
      $term_entities = array();
      if ($load_entities) {
        $term_entities = $this->loadMultiple(array_keys($this->treeTerms[$vid]));
      }

      $max_depth = (!isset($max_depth)) ? count($this->treeChildren[$vid]) : $max_depth;
      $tree = array();

      // Keeps track of the parents we have to process, the last entry is used
      // for the next processing step.
      $process_parents = NULL;
      if (isset($this->treeChildren[$vid][0])) {
        $process_parents = array_keys($this->treeChildren[$vid][0]);
      }

      // $process_parents[] = $parent;
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
  public function resetWeights($vid) {
    // SKOS doesn't use weights...
  }

  /**
   * {@inheritdoc}
   */
  public function getNodeTerms(array $nids, array $vocabs = array(), $langcode = NULL) {
    // @todo Test this.
    $query = db_select('taxonomy_index', 'tn');
    $query->fields('tn', array('tid'));
    $query->addField('tn', 'nid', 'node_nid');
    $query->condition('tn.nid', $nids, 'IN');

    $results = array();
    $all_tids = array();
    foreach ($query->execute() as $term_record) {
      $results[$term_record->node_nid][] = $term_record->tid;
      $all_tids[] = $term_record->tid;
    }

    $all_terms = $this->loadMultiple($all_tids);
    $terms = array();
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
    $this->parents = array();
    $this->parentsAll = array();
    $this->children = array();
    $this->treeChildren = array();
    $this->treeParents = array();
    $this->treeTerms = array();
    $this->trees = array();
  }

}
