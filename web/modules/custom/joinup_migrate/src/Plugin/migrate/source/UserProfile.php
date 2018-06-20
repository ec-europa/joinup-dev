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
      'photo_id' => $this->t('User photo ID'),
      'professional_profile' => $this->t('Professional profile'),
      'social_media' => $this->t('Social media accounts'),
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
      'photo_id',
      'professional_profile',
      'facebook',
      'twitter',
      'linkedin',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $countries = $this->getCountries([$row->getSourceProperty('vid')]);
    // We don't migrate nationality in the case when the source user has more
    // than one country set. The user will have to manually update its profile.
    // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-2960
    $countries = count($countries) === 1 ? $countries : [];
    $row->setSourceProperty('country', $countries);

    // Social media accounts.
    $social_media = [];
    foreach (static::SOCIAL_MEDIA as $service => $pattern) {
      if (($value = trim($row->getSourceProperty($service))) && preg_match($pattern, $value, $found)) {
        $social_media[$service]['value'] = $found[1];
      }
    }
    $row->setSourceProperty('social_media', $social_media ?: NULL);

    return parent::prepareRow($row);
  }

  /**
   * Social media validation regular expressions.
   */
  const SOCIAL_MEDIA = [
    // We are not doing any check of the validity or integrity of the account
    // IDs, because it would not be in the scope of migration. We are only
    // removing the host prefix and migrate the rest.
    'facebook' => '/(?:(?:https?:\/\/)?(?:www\.)?(?:facebook|fb|m\.facebook)\.(?:com|me)\/)?(.+)$/i',
    'twitter' => '/(?:@|(?:https?:\/\/)?(?:www\.)?(?:twitter\.com\/)?(?:#!\/)?)?(.+)$/i',
    'linkedin' => '/(?:(?:https?:\/\/)?(?:www\.)?(?:linkedin\.com\/)?)?(.+)$/i',
  ];

}
