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
    $row->setSourceProperty('country', $this->getCountries($row->getSourceProperty('profile_vid')));
    return parent::prepareRow($row);
  }

}
