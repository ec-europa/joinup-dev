<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Core\Database\Database;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;

/**
 * Migrates policy domain terms.
 *
 * @MigrateSource(
 *   id = "policy_domain"
 * )
 */
class PolicyDomain extends SourcePluginBase {

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
  public function fields() {
    return [
      'tid' => $this->t('Term ID'),
      'name' => $this->t('Name'),
      'parent' => $this->t('Parent'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    $db = Database::getConnection('default', 'migrate');

    $query = $db->select('d8_solution', 's')
      ->fields('s', ['policy', 'policy2'])
      ->union(
        $db->select('d8_collection', 'c')
          ->fields('c', ['policy', 'policy2'])
      );

    $terms = [];
    foreach ($query->execute()->fetchAll() as $row) {
      $parent = $row->policy;
      $name = $row->policy2;

      // Terms lacking a parent will go temporary under a 'UNCLASSIFIED' term
      // and will be logged as inconsistency, to be fixed in the .xlsx file.
      if (empty($parent)) {
        $parent = 'UNCLASSIFIED';
        if (!isset($terms["$parent:$name"])) {
          $this->migration->getIdMap()->saveMessage(['parent' => '', 'name' => $name], "Term '$name' lacks a parent.");
        }
      }

      // Store the parent term.
      static::addTerm($terms, $parent);
      // Store the term itself.
      static::addTerm($terms, $name, $parent);
    }

    return new \ArrayIterator(array_values($terms));
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return 'Policy domain';
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
    $key = "$parent:$name";
    if (!isset($terms[$key]) && !empty($name)) {
      $terms[$key] = [
        'parent' => $parent,
        'name' => $name,
      ];
    }
  }

}
