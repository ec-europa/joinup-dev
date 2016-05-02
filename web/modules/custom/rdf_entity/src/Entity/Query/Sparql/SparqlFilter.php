<?php

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
  public function filter($filter, $type = 'FILTER') {
    $this->filters[] = ['filter' => $filter, 'type' => $type];

    return $this;
  }

  /**
   * Implements \Drupal\Core\Entity\Query\ConditionInterface::compile().
   */
  public function compile($query) {
    foreach ($this->filters as $filter) {
      if (in_array($filter['type'], ['FILTER EXISTS', 'FILTER NOT EXISTS'])) {
        $query->query .= $filter['type'] . ' {' . $filter['filter'] . "} .\n";
      }
      else {
        $query->query .= $filter['type'] . ' (' . $filter['filter'] . ") .\n";
      }
    }
  }

}
