<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Core\Database\Database;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Migrates parent custom pages.
 *
 * @MigrateSource(
 *   id = "custom_page_parent"
 * )
 */
class CustomPageParent extends SourcePluginBase {

  /**
   * Connection to source database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $db;

  /**
   * A list of collection components cardinality.
   *
   * @var int[]
   */
  protected $cardinality;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->db = Database::getConnection('default', 'migrate');
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'group_nid' => [
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
      'group_nid' => $this->t('ID'),
      'group_title' => $this->t('Title'),
      'collection' => $this->t('Collection'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    $items = $this->db->select('d8_custom_page', 'n')
      ->distinct()
      ->fields('n', [
        'collection',
        'group_nid',
        'group_title',
      ])
      ->condition('n.exclude', 0)
      ->execute()
      ->fetchAll(\PDO::FETCH_ASSOC);

    $rows = [];
    foreach ($items as $row) {
      $nid = (int) $row['group_nid'];
      if ($this->getCardinality($nid) > 1) {
        $rows[] = $row;
      }
    }

    return new \ArrayIterator($rows);
  }

  /**
   * Gets the cardinality of the collection for a give collection and component.
   *
   * @param int $nid
   *   The component node ID.
   *
   * @return int
   *   The cardinality.
   */
  protected function getCardinality($nid) {
    if (!isset($this->cardinality)) {
      $result = $this->db->select('d8_custom_page', 'n')
        ->fields('n', ['collection', 'group_nid'])
        ->condition('n.exclude', 0)
        ->execute()->fetchAll(\PDO::FETCH_ASSOC);
      $cardinality = [];
      foreach ($result as $item) {
        $cardinality[$item['collection']][$item['group_nid']] = TRUE;
      }
      array_walk($cardinality, function (&$value) {
        $value = count($value);
      });

      foreach ($result as $item) {
        $this->cardinality[(int) $item['group_nid']] = $cardinality[$item['collection']];
      }
    }

    return isset($this->cardinality[$nid]) ? $this->cardinality[$nid] : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return 'custom_page_parent';
  }

}
