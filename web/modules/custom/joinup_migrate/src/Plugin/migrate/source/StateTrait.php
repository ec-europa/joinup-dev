<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Provides helper methods for state field migration.
 */
trait StateTrait {

  /**
   * Sets the state for a given node revision ID.
   *
   * @param \Drupal\migrate\Row $row
   *   The migration row.
   */
  protected function setState(Row &$row) {
    // If there's an explicit value enforced in the mapping table, use it.
    if ($item_state = $row->getSourceProperty('item_state')) {
      $state = $item_state;
    }
    // Otherwise use the mapped Drupal 6 value.
    else {
      $migration_id = $row->getSourceProperty('plugin');
      $legacy_type = $row->getSourceProperty('type');
      $legacy_state = $row->getSourceProperty('state');
      $state = NULL;
      if (isset(static::$stateMap[$migration_id][$legacy_type][$legacy_state])) {
        $state = static::$stateMap[$migration_id][$legacy_type][$legacy_state];
      }
    }
    $row->setSourceProperty('state', $state);
  }

  /**
   * State mapping.
   *
   * @var array
   */
  protected static $stateMap = [
    'solution' => [
      'asset_release' => [
        'draft' => 'draft',
        'proposed' => 'proposed',
        'suspended' => 'needs_update',
        'validated' => 'validated',
        'in assessment' => 'needs_update',
        'assessed' => 'validated',
        'requested deletion' => 'deletion_request',
        'blacklisted' => 'blacklisted',
      ],
      'project_project' => [
        'draft' => 'draft',
        'proposed' => 'proposed',
        'suspended' => 'proposed',
        'validated' => 'validated',
      ],
    ],
    'asset_release' => [
      'asset_release' => [
        'draft' => 'draft',
        'proposed' => 'proposed',
        'suspended' => 'needs_update',
        'validated' => 'validated',
        'in assessment' => 'needs_update',
        'requested deletion' => 'deletion_request',
        'blacklisted' => 'blacklisted',
      ],
    ],
  ];

}
