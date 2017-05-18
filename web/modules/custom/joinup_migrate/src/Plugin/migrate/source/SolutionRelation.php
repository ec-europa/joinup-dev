<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Core\Database\Database;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;

/**
 * Migrates the relations between solutions.
 *
 * @MigrateSource(
 *   id = "solution_relation"
 * )
 */
class SolutionRelation extends SourcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => [
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
      'related_solutions' => $this->t('Related solution'),
      'translations' => $this->t('Translation'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    // The relationship types are:
    // @code
    // [
    //   'Next Version' => '0',
    //   'Previous Version' => '1',
    //   'Translation' => '2',
    //   'Included Asset' => '3',
    //   'Related Asset' => '4',
    //   'Sample' => '5',
    // ];
    // @endcode
    $db = Database::getConnection('default', 'migrate');
    $all_solutions = $db->select('d8_solution', 's')
      ->fields('s', ['nid'])->execute()->fetchCol();
    $items = $db->select('d8_solution_relation', 'sr')
      ->fields('sr')->execute()->fetchAll();

    $rows = [];
    foreach ($items as $item) {
      $nid = (int) $item->nid;
      $value = unserialize($item->value);
      if (!is_array($value)) {
        continue;
      }
      // The relation type shold be strictly an integer as string. Strip out
      // 'Next Version' and 'Previous Version'.
      if (
        !isset($value['field_asset_node_relationship'][0]['value'])
        || !in_array($value['field_asset_node_relationship'][0]['value'], [
          '2',
          '3',
          '4',
          '5',
        ], TRUE)
      ) {
        continue;
      }
      $type = $value['field_asset_node_relationship'][0]['value'];

      // Allow only a valid target node ID.
      if (!isset($value['field_asset_node_reference_node'][0]['nid']) || !is_string($value['field_asset_node_reference_node'][0]['nid']) || !ctype_digit($value['field_asset_node_reference_node'][0]['nid']) || $value['field_asset_node_reference_node'][0]['nid'] === '0') {
        continue;
      }

      $target_id = (int) $value['field_asset_node_reference_node'][0]['nid'];

      // Don't allow references to self.
      if ($target_id === $nid) {
        continue;
      }
      // The target should be a valid solution.
      if (!in_array($target_id, $all_solutions)) {
        continue;
      }

      $rows[$nid]['nid'] = $nid;
      if ($type === '2') {
        // A translation.
        $rows[$nid]['translations'][] = $target_id;
      }
      else {
        // Other relation.
        $rows[$nid]['related_solutions'][] = $target_id;
      }
    }

    return new \ArrayIterator($rows);
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return 'solution_relation';
  }

}
