<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

/**
 * Common methods to retrieve the country.
 */
trait CountryTrait {

  /**
   * Source country hierarchy.
   *
   * @var array
   */
  protected static $countryHierarchy;

  /**
   * Country mapping.
   *
   * @var array
   */
  protected $countryCorrection = [
    'Faroe Islands' => 'Faroes',
    'Fyrom' => 'Former Yugoslav Republic of Macedonia',
  ];

  /**
   * Gets a list of countries based on the node vid.
   *
   * @param int $vid
   *   The node vid.
   *
   * @return string[]
   *   A list of country names.
   */
  protected function getCountries($vid) {
    $query = $this->select('term_node', 'tn')
      ->fields('td', ['name'])
      ->condition('td.vid', 26)
      ->condition('tn.vid', $vid);
    $query->join('term_data', 'td', 'tn.tid = td.tid');

    $terms = [];
    foreach ($query->execute()->fetchCol() as $term) {
      if ($countries = $this->getCountriesByContinent($term)) {
        // Replace continents with their component countries.
        $terms = array_merge($terms, $countries);
      }
      elseif (!in_array($term, $terms)) {
        $terms[] = $term;
      }
    }

    // Corrections.
    return array_map(function ($term) {
      return isset($this->countryCorrection[$term]) ? $this->countryCorrection[$term] : $term;
    }, $terms);
  }

  /**
   * Gets the country hierarchy.
   *
   * @param string $continent
   *   The continent.
   *
   * @return array[]
   *   Associative array keyed by continent and having a list of component
   *   countries as values.
   */
  protected function getCountriesByContinent($continent) {
    if (!isset(static::$countryHierarchy)) {
      // Populate the source country hierarchy.
      $query = $this->select('term_data', 'd')
        ->fields('d', ['name'])
        ->condition('d.vid', 26)
        ->condition('h.parent', 0, '>');
      $query->join('term_hierarchy', 'h', 'd.tid = h.tid');
      $query->join('term_data', 'd1', 'h.parent = d1.tid');
      $query->addExpression('d1.name', 'parent');
      $query->orderBy('d1.name')->orderBy('d.name');
      foreach ($query->execute()->fetchAll() as $country) {
        static::$countryHierarchy[$country['parent']][] = $country['name'];
      }
    }
    return isset(static::$countryHierarchy[$continent]) ? static::$countryHierarchy[$continent] : NULL;
  }

}
