<?php

declare(strict_types = 1);

namespace Drupal\tallinn;

/**
 * Provide a list of countries subject of Tallinn reports.
 */
class Tallinn {

  /**
   * The Tallinn community id.
   *
   * @var string
   */
  const TALLINN_COMMUNITY_ID = 'http://data.europa.eu/w21/5f4c0dae-f521-4d00-a0cf-e1dce0a128a3';

  /**
   * The Tallinn reports countries.
   *
   * @var string[]
   */
  const COUNTRIES = [
    'AT' => "Austria",
    'BE' => "Belgium",
    'BG' => "Bulgaria",
    'HR' => "Croatia",
    'CY' => "Cyprus",
    'CZ' => "Czech Republic",
    'DK' => "Denmark",
    'EE' => "Estonia",
    'FI' => "Finland",
    'FR' => "France",
    'DE' => "Germany",
    'GR' => "Greece",
    'HU' => "Hungary",
    'IE' => "Ireland",
    'IT' => "Italy",
    'LV' => "Latvia",
    'LT' => "Lithuania",
    'LU' => "Luxembourg",
    'MT' => "Malta",
    'NL' => "The Netherlands",
    'PL' => "Poland",
    'PT' => "Portugal",
    'RO' => "Romania",
    'SK' => "Slovak Republic",
    'SI' => "Slovenia",
    'ES' => "Spain",
    'SE' => "Sweden",
    'GB' => "United Kingdom",
    'IS' => "Iceland",
    'LI' => "Liechtenstein",
    'NO' => "Norway",
    'CH' => "Switzerland",
  ];

}
