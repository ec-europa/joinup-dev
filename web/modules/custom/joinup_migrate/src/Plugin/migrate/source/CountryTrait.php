<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

/**
 * Common methods to retrieve the country.
 */
trait CountryTrait {

  /**
   * Country correction mapping.
   *
   * A country can be split into multiple countries by setting an array. Setting
   * NULL means "this country is not migrated".
   *
   * @var array
   */
  protected static $countryCorrection = [
    'Africa' => NULL,
    'Asia' => NULL,
    'British Antarctic Territory' => 'Antarctica',
    'Central and South America' => NULL,
    "Cote d'Ivoire" => 'Côte d’Ivoire',
    'EU Institutions' => 'European Union',
    'Europe' => NULL,
    'Faroe Islands' => 'Faroes',
    'Fyrom' => 'Former Yugoslav Republic of Macedonia',
    'Gambia' => 'The Gambia',
    'Gilbert and Ellice Islands' => ['Kiribati', 'Tuvalu'],
    'International Organizations' => NULL,
    'Midway Islands' => 'United States Minor Outlying Islands',
    'North America' => NULL,
    'Oceania' => NULL,
    'Other' => NULL,
    'Pan European' => NULL,
    'Queen Maud Land' => 'Antarctica',
    'Sao Tome and Pri­ncipe' => 'São Tomé and Príncipe',
    'South Korea/The Republic of Korea' => 'South Korea',
    'US Miscellaneous Pacific Islands' => 'United States Minor Outlying Islands',
    'Wake Islands' => 'United States Minor Outlying Islands',
  ];

  /**
   * Gets a list of countries based on a list of node revision IDs.
   *
   * @param int[] $vids
   *   A list of node revision IDs.
   *
   * @return string[]
   *   A list of country names.
   */
  protected function getCountries(array $vids) {
    if (empty($vids)) {
      return [];
    }

    $query = $this->select('term_node', 'tn')
      ->distinct()
      ->fields('td', ['name'])
      // The country vocabulary has vid equals 26.
      ->condition('td.vid', 26)
      ->condition('tn.vid', $vids, 'IN');
    $query->join('term_data', 'td', 'tn.tid = td.tid');
    $terms = $query->execute()->fetchCol();

    // Corrections.
    $countries = [];
    foreach ($terms as $term) {
      if (array_key_exists($term, static::$countryCorrection)) {
        if (!$country = static::$countryCorrection[$term]) {
          // This country should not be migrated.
          continue;
        }
        $countries = array_merge($countries, (array) $country);
      }
      else {
        $countries[] = $term;
      }
    }

    return array_unique($countries);
  }

}
