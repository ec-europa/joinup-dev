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
    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = Database::getConnection()->select('joinup_migrate_mapping', 'm', ['fetch' => \PDO::FETCH_ASSOC])
      ->condition('m.type', 'asset_release')
      ->condition('m.migrate', 1);
    $query->join('joinup_migrate_collection', 'c', 'm.collection = c.collection');
    $query->addExpression('c.policy', 'collection_parent');
    $query->addExpression('c.policy2', 'collection_name');
    $query->addExpression('m.policy', 'solution_parent');
    $query->addExpression('m.policy2', 'solution_name');

    $terms = [];
    foreach ($query->execute()->fetchAll() as $row) {
      foreach (['collection', 'solution'] as $type) {
        $parent = $row["{$type}_parent"];
        $name = $row["{$type}_name"];

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
