<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Migrates collections.
 *
 * @MigrateSource(
 *   id = "contact"
 * )
 */
class Contact extends JoinupSqlBase {

  use ContactTrait;
  use MappingTrait;

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => [
        'type' => 'integer',
        'alias' => 'n',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'nid' => $this->t('ID'),
      'uri' => $this->t('URI'),
      'name' => $this->t('Name'),
      'mail' => $this->t('E-mail'),
      'webpage' => $this->t('Webpage'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Build a list of contact info allowed NIDs by querying only the objects
    // that will be migrated (parent collections and solutions).
    $allowed_nids = array_values(array_unique(array_merge(
      $this->getCollectionContacts(),
      $this->getSolutionContacts()
    )));

    $this->alias['node'] = 'n';
    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->select('node', $this->alias['node'])
      ->fields($this->alias['node'], ['nid', 'vid', 'title'])
      ->condition("{$this->alias['node']}.status", 1)
      ->condition("{$this->alias['node']}.type", 'contact_point');

    if ($allowed_nids) {
      // Limit publishers only to those referred by migrated repositories and
      // interoperability solutions.
      $query->condition("{$this->alias['node']}.nid", $allowed_nids, 'IN');
    }
    else {
      // It there's no allowed NID, return nothing.
      $query->condition(1, 2);
    }

    $query->leftJoin('content_field_contact_point_name', 'cn', "{$this->alias['node']}.vid = cn.vid");
    $query->leftJoin('content_field_contact_point_mail', 'cm', "{$this->alias['node']}.vid = cm.vid");
    $query->leftJoin('content_field_contact_point_web_page', 'cw', "{$this->alias['node']}.vid = cw.vid");

    $query->addExpression('cn.field_contact_point_name_value', 'name');
    $query->addExpression('cm.field_contact_point_mail_value', 'mail');
    $query->addExpression('cw.field_contact_point_web_page_url', 'webpage');

    return $query
      // Assure the URI field.
      ->addTag('uri');
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $name = trim($row->getSourceProperty('name'));
    $title = trim($row->getSourceProperty('title'));
    $name = !$name || ($name == $title) ? $title : trim("$title $name");
    $row->setSourceProperty('name', $name);

    return parent::prepareRow($row);
  }

}
