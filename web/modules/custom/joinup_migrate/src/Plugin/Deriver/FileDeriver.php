<?php

namespace Drupal\joinup_migrate\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Database\Database;

/**
 * Deriver for 'file:*' migrations.
 */
class FileDeriver extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $source_db = Database::getConnection('default', 'migrate');

    // File tables/views names are prefixed with 'd8_file_*'.
    $tables = $source_db->query("SHOW TABLES LIKE 'd8\_file\_%'")->fetchCol();
    foreach ($tables as $table) {
      $derivative_id = substr($table, 8);
      $this->derivatives[$derivative_id] = $base_plugin_definition;
    }

    // Inline files is a special case, with its own source plugin.
    $this->derivatives['inline'] = $base_plugin_definition;
    $this->derivatives['inline']['source']['plugin'] = 'file_inline';

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
