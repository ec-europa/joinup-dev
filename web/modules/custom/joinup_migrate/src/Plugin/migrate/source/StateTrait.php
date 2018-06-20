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
    $state = NULL;
    $migration_id = $row->getSourceProperty('plugin');

    // If there's an explicit value enforced in the mapping table, use it.
    if (($migration_id !== 'owner') && ($item_state = $row->getSourceProperty('item_state'))) {
      $state = $item_state;
    }
    else {
      $legacy_type = $row->getSourceProperty('type');
      $legacy_state = $row->getSourceProperty('state');
      $legacy_status = (bool) $row->getSourceProperty('status');

      // If the Drupal 6 published status is TRUE, then the Drupal 8 state is
      // enforced to 'validated'.
      // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3916
      if ($legacy_status) {
        $state = 'validated';
      }
      // If the Drupal 6 state is 'validated' but the published status is FALSE,
      // then the Drupal 8 state is enforced 'draft'.
      // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3916
      elseif (!$legacy_status && $legacy_state === 'validated') {
        $state = 'draft';
      }
      // Use the mapping.
      elseif (isset(static::$stateMap[$migration_id][$legacy_type][$legacy_state])) {
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
    'release' => [
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
    'owner' => [
      'publisher' => [
        'draft' => 'needs_update',
        'proposed' => 'validated',
        'validated' => 'validated',
        'suspended' => 'needs_update',
      ],
    ],
    'document' => [
      'case_epractice' => [
        'draft' => 'draft',
        'proposed' => 'proposed',
        'validated' => 'validated',
        'suspended' => 'needs_update',
        'created' => 'proposed',
        'requested publication' => 'proposed',
        'published' => 'validated',
      ],
      'document' => [
        'draft' => 'draft',
        'proposed' => 'proposed',
        'validated' => 'validated',
        'suspended' => 'needs_update',
        'created' => 'proposed',
        'requested publication' => 'proposed',
        'published' => 'validated',
      ],
      'presentation' => [
        'draft' => 'draft',
        'proposed' => 'proposed',
        'validated' => 'validated',
        'suspended' => 'needs_update',
        'created' => 'proposed',
        'requested publication' => 'proposed',
        'published' => 'validated',
      ],
      'factsheet' => [
        'validated' => 'validated',
      ],
      'legaldocument' => [
        'draft' => 'draft',
        'validated' => 'validated',
      ],
    ],
    'news' => [
      'news' => [
        'draft' => 'draft',
        'proposed' => 'proposed',
        'validated' => 'validated',
        'suspended' => 'needs_update',
        'created' => 'proposed',
        'requested publication' => 'proposed',
        'published' => 'validated',
      ],
    ],
    'event' => [
      'event' => [
        'draft' => 'draft',
        'proposed' => 'proposed',
        'validated' => 'validated',
        'suspended' => 'needs_update',
        'created' => 'proposed',
        'requested publication' => 'proposed',
        'published' => 'validated',
      ],
    ],
  ];

}
