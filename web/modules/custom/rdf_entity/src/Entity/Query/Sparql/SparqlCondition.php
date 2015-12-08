<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Query\Null\Condition.
 */

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
    $this->conditions[] = array(
      'field' => $field,
      'value' => $value,
      'operator' => $operator,
      'langcode' => $langcode,
    );

    return $this;
  }

  /**
   * Implements \Drupal\Core\Entity\Query\ConditionInterface::compile().
   */
  public function compile($query) {
    foreach ($this->conditions() as $condition) {
      if ($condition['field'] == 'label' && $condition['operator'] == 'CONTAINS') {
        $query->query .= '?entity <http://purl.org/dc/terms/title> ?label.
  filter( regex(?label, "' . $condition['value'] . '" ))';
        //$query->query .= 'filter( regex(?label, "' . $condition['value'] . '" ))';
      }
    }

  }

  /**
   * Implements \Drupal\Core\Entity\Query\ConditionInterface::exists().
   */
  public function exists($field, $langcode = NULL) {
    // return $this->condition($field, NULL, 'IS NOT NULL', $langcode);
  }

  /**
   * Implements \Drupal\Core\Entity\Query\ConditionInterface::notExists().
   */
  public function notExists($field, $langcode = NULL) {
    // return $this->condition($field, NULL, 'IS NULL', $langcode);
  }

}
