<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

/**
 * Migrates newsletter types.
 *
 * @MigrateSource(
 *   id = "newsletter_type"
 * )
 */
class NewsletterType extends JoinupSqlBase {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'newsletter' => [
        'type' => 'string',
        'alias' => 'n',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return ['newsletter' => $this->t('Name')];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('d8_newsletter_type', 'n')
      ->fields('n', ['newsletter']);
  }

}
