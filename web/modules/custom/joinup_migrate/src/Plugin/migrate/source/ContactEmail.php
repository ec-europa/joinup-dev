<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Migrates E-mail contact as contact.
 *
 * @MigrateSource(
 *   id = "contact_email"
 * )
 */
class ContactEmail extends JoinupSqlBase {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'name' => [
        'type' => 'string',
        'alias' => 'c',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'name' => $this->t('Name'),
      'mail' => $this->t('E-mail'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('d8_contact_email', 'c')->fields('c');
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $name = $row->getSourceProperty('name');
    if (\Drupal::service('email.validator')->isValid($name)) {
      $row->setSourceProperty('mail', $name);
    }
    return parent::prepareRow($row);
  }

}
