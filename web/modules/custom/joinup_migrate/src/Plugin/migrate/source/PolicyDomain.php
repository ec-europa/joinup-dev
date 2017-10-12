<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\migrate_spreadsheet\Plugin\migrate\source\Spreadsheet;

/**
 * Migrates policy domain terms.
 *
 * @MigrateSource(
 *   id = "policy_domain"
 * )
 */
class PolicyDomain extends Spreadsheet {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'parent' => [
        'type' => 'string',
      ],
      'name' => [
        'type' => 'string',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    /** @var \Drupal\migrate_spreadsheet\SpreadsheetIteratorInterface $iterator */
    $iterator = parent::initializeIterator();

    $iterator->rewind();
    $terms = [];
    $parent = NULL;
    while ($iterator->valid()) {
      $row = $iterator->current();
      $parent = $row['A'] ?: $parent;
      $name = $row['B'];

      // Store the parent term.
      static::addTerm($terms, $parent);
      // Store the term itself.
      static::addTerm($terms, $name, $parent);

      $iterator->next();
    }

    return new \ArrayIterator($terms);
  }

  /**
   * Adds a term to the terms list.
   *
   * @param array $terms
   *   The list of terms to be updated.
   * @param string $name
   *   The term name to be added.
   * @param string $parent
   *   (optional) The parent term. In case of top level terms can be omitted.
   */
  protected static function addTerm(array &$terms, $name, $parent = '') {
    $name = trim($name);
    $parent = trim($parent);

    $key = "$parent:$name";
    if (!isset($terms[$key]) && !empty($name)) {
      $terms[$key] = [
        'parent' => $parent,
        'name' => $name,
      ];
    }
  }

}
