<?php
/**
 * @file
 * Provides FILTER capabilities to Sparql queries.
 */

namespace Drupal\rdf_entity\Entity\Query\Sparql;

/**
 * Class SparqlFilter.
 *
 * Allows to add filters to a sparql query.
 *
 * @package Drupal\rdf_entity\Entity\Query\Sparql
 */
class SparqlFilter {

  /**
   * Array of filters.
   *
   * @var array
   */
  protected $filters = array();

  /**
   * Add a filter.
   */
  public function filter($filter) {
    $this->filters[] = $filter;

    return $this;
  }

  /**
   * Implements \Drupal\Core\Entity\Query\ConditionInterface::compile().
   */
  public function compile($query) {
    foreach ($this->filters as $filter) {
      $query->query .= 'FILTER (' . $filter . ") .\n";
    }
  }

}
