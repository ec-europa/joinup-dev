<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Provides a migration source plugin for user profiles.
 *
 * @MigrateSource(
 *   id = "user_profile"
 * )
 */
class UserProfile extends UserBase {

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
   * {@inheritdoc}
   */
  public function fields() {
    return parent::fields() + [
      'last_name' => $this->t('Family name'),
      'first_name' => $this->t('First name'),
      'company_name' => $this->t('Company'),
      'professional_profile' => $this->t('Professional profile'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();

    $this->alias['node'] = $query->leftJoin('node', 'node', "u.uid = %alias.uid AND %alias.type = 'profile'");
    $this->alias['profile'] = $query->leftJoin('content_type_profile', 'profile', "{$this->alias['node']}.vid = %alias.vid");
    $this->alias['profile'] = $query->leftJoin('content_type_profile', 'profile', "{$this->alias['node']}.vid = %alias.vid");

    $query->addExpression("{$this->alias['profile']}.vid", 'profile_vid');
    $query->addExpression("{$this->alias['profile']}.field_lastname_value", 'last_name');
    $query->addExpression("{$this->alias['profile']}.field_firstname_value", 'first_name');
    $query->addExpression("{$this->alias['profile']}.field_company_name_value", 'company_name');
    $query->addExpression("{$this->alias['profile']}.field_professional_profile_value", 'professional_profile');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $query = $this->select('term_node', 'tn')
      ->fields('td', ['name'])
      ->condition('td.vid', 26)
      ->condition('tn.vid', $row->getSourceProperty('profile_vid'));
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
    $terms = array_map(function ($term) {
      return isset($this->countryCorrection[$term]) ? $this->countryCorrection[$term] : $term;
    }, $terms);

    $row->setSourceProperty('country', $terms);

    return parent::prepareRow($row);
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
