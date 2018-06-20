<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Migrates newsletter subscribers.
 *
 * @MigrateSource(
 *   id = "newsletter_subscriber"
 * )
 */
class NewsletterSubscriber extends JoinupSqlBase {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'id' => [
        'type' => 'integer',
        'alias' => 's',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'id' => $this->t('ID'),
      'status' => $this->t('Status'),
      'mail' => $this->t('Mail'),
      'uid' => $this->t('User ID'),
      'langcode' => $this->t('Language code'),
      'timestamp' => $this->t('Timestamp'),
      'newsletter' => $this->t('Subscribed newsletters'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('d8_newsletter_subscriber', 's')->fields('s', [
      'id',
      'status',
      'mail',
      'uid',
      'langcode',
      'timestamp',
      'newsletter',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $newsletters = $row->getSourceProperty('newsletter');
    if ($newsletters) {
      $subscriptions = [];
      foreach (explode(',', $newsletters) as $newsletter) {
        $subscriptions[] = [
          'target_id' => $newsletter,
          'status' => $row->getSourceProperty('status'),
          'timestamp' => $row->getSourceProperty('timestamp'),
        ];
      }
    }
    else {
      $subscriptions = [];
    }
    $row->setSourceProperty('subscriptions', $subscriptions);

    return parent::prepareRow($row);
  }

}
