<?php

namespace Drupal\rdf_entity\Entity\Query\Sparql;

use Drupal\Core\Entity\Query\ConditionFundamentals;
use Drupal\Core\Entity\Query\ConditionInterface;

/**
 * Defines the condition class for the null entity query.
 */
class SparqlCondition extends ConditionFundamentals implements ConditionInterface {

  /**
   * {@inheritdoc}
   */
  public function condition($subject = NULL, $predicate = NULL, $object = NULL, $operator = NULL, $langcode = NULL) {
    $this->conditions[] = [
      'subject' => $subject,
      'predicate' => $predicate,
      'object' => $object,
    ];

    return $this;
  }

  /**
   * Implements \Drupal\Core\Entity\Query\ConditionInterface::compile().
   */
  public function compile($query) {
    foreach ($this->conditions() as $condition) {
      $query->query .= $condition['subject'] . ' ' . $condition['predicate'] . ' ' . $condition['object'] . ".\n";
    }

  }

  /**
   * Implements \Drupal\Core\Entity\Query\ConditionInterface::exists().
   */
  public function exists($field, $langcode = NULL) {
    $this->query->condition('_field_exists', $field, 'EXISTS');
  }

  /**
   * Implements \Drupal\Core\Entity\Query\ConditionInterface::notExists().
   */
  public function notExists($field, $langcode = NULL) {
    $this->query->condition('_field_exists', $field, 'NOT EXISTS');
  }

  /**
   * {@inheritdoc}
   */
  public function __clone() {}

}
