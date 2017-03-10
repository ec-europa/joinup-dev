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

  use CountryTrait;

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return parent::fields() + [
      'last_name' => $this->t('Family name'),
      'first_name' => $this->t('First name'),
      'company_name' => $this->t('Company'),
      'country' => $this->t('Nationality'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return parent::query()->fields('u', [
      'last_name',
      'first_name',
      'company_name',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $countries = $this->getCountries([$row->getSourceProperty('vid')], FALSE);
    // We don't migrate nationality in the case when the source user has more
    // than one country set. The user will have to manually update its profile.
    // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-2960
    $countries = count($countries) === 1 ? $countries : [];
    $row->setSourceProperty('country', $countries);
    return parent::prepareRow($row);
  }

}
