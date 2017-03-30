<?php

namespace Drupal\rdf_entity\Entity\Query\Sparql;

use Drupal\Core\Entity\Query\ConditionFundamentals;
use Drupal\Core\Entity\Query\ConditionInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\rdf_entity\RdfGraphHandler;
use Drupal\rdf_entity\RdfMappingHandler;

/**
 * Defines the condition class for the null entity query.
 *
 * @todo: Build a ConditionInterface that extends the ConditionInterface below.
 */
class SparqlCondition extends ConditionFundamentals implements ConditionInterface {

  /**
   * Array of filters.
   *
   * This is similar to the conditions but for SPARQL performance, the filters
   * need to be placed after the triples in the 'where' clause because it makes
   * the query faster.
   *
   * @var array
   */
  protected $filters = [];

  /**
   * The rdf graph handler service object.
   *
   * @var \Drupal\rdf_entity\RdfGraphHandler
   */
  protected $graphHandler;

  /**
   * The rdf mapping handler service object.
   *
   * @var \Drupal\rdf_entity\RdfMappingHandler
   */
  protected $mappingHandler;

  /**
   * Provides a map of filter operators to operator options.
   *
   * @var array
   */
  protected static $filterOperatorMap = [
    // @todo: remove the comments here.
    // Filter.
    'IN' => ['delimiter' => ', ', 'prefix' => '', 'suffix' => ''],
    // Filter.
    'NOT IN' => ['delimiter' => ', ', 'prefix' => '', 'suffix' => ''],
    // @todo: We are not saving empty values. Do we need this?
    // @todo: If we support empty values, all other queries should be converted
    // to optional.
    'IS NULL' => ['use_value' => FALSE],
    // Normal triple with mapped predicate.
    'IS NOT NULL' => ['use_value' => FALSE],
    // Filter with regex.
    'LIKE' => ['prefix' => 'regex(', 'suffix' => ')'],
    // Filter with inverse regex?
    'NOT LIKE' => ['prefix' => '!regex(', 'suffix' => ')'],
    'EXISTS' => ['prefix' => 'EXISTS {', 'suffix' => '}'],
    'NOT EXISTS' => ['prefix' => 'NOT EXISTS {', 'suffix' => '}'],
    '<' => ['prefix' => '', 'suffix' => ''],
    '>' => ['prefix' => '', 'suffix' => ''],
    '>=' => ['prefix' => '', 'suffix' => ''],
    '<=' => ['prefix' => '', 'suffix' => ''],
  ];

  /**
   * The default bundle predicate.
   *
   * @var array
   */
  protected $typePredicate = '<http://www.w3.org/1999/02/22-rdf-syntax-ns#type>';


  /**
   * An array of conditions in their string version.
   *
   * These are formed during the compilation phase.
   *
   * @var string[]
   */
  protected $conditionFragments;

  /**
   * An array of field names.
   *
   * These will be used to add the conditions of the fields that are not direct
   * e.g. adding a field that needs its value to be filtered.
   *
   * @var string[]
   */
  protected $fields;

  /**
   * An array of field names and their corresponding mapping.
   *
   * @var string[]
   */
  protected $fieldMappings;

  /**
   * An array of conditions regarding fields with multiple possible mappings.
   *
   * @var array
   */
  protected $fieldMappingConditions;

  /**
   * The entity type id key.
   *
   * @var string
   */
  protected $idKey;

  /**
   * The entity type bundle key.
   *
   * @var string
   */
  protected $bundleKey;

  /**
   * The entity type label key.
   *
   * @var string
   */
  protected $labelKey;

  /**
   * The bundle id.
   *
   * @var string
   */
  protected $entityBundle;

  /**
   * The string version of the condition fragments.
   *
   * @var string
   */
  protected $stringVersion;

  /**
   * Whether the condition has been compiled.
   *
   * @var bool
   */
  private $isCompiled;

  // @todo: Do we need this?
  // @todo: This has to go to the interface.
  const ID_KEY = '?entity';

  /**
   * {@inheritdoc}
   */
  public function __construct($conjunction, QueryInterface $query, array $namespaces, RdfGraphHandler $rdf_graph_handler, RdfMappingHandler $rdf_mapping_handler) {
    parent::__construct($conjunction, $query, $namespaces);
    $this->graphHandler = $rdf_graph_handler;
    $this->mappingHandler = $rdf_mapping_handler;
    $this->bundleKey = $query->getEntityType()->getKey('bundle');
    $this->idKey = $query->getEntityType()->getKey('id');
    $this->labelKey = $query->getEntityType()->getKey('label');
    // Initialize variable to avoid warnings;.
    $this->fieldMappingConditions = [];
  }

  /**
   * A list of properties regarding the query conjunction.
   *
   * @var array
   */
  protected static $conjunctionMap = [
    'AND' => ['delimeter' => ' . ', 'prefix' => '', 'suffix' => ''],
    'OR' => ['delimeter' => ' UNION ', 'prefix' => '{ ', 'suffix' => ' }'],
  ];

  /**
   * {@inheritdoc}
   *
   * @todo: handle the langcode.
   */
  public function condition($field = NULL, $value = NULL, $operator = NULL, $langcode = NULL) {
    if ($operator === NULL) {
      $operator = '=';
    }

    if ($this->conjunction == 'OR') {
      $sub_condition = $this->query->andConditionGroup();
      $sub_condition->condition($field, $value, $operator, $langcode);
      $this->conditions[] = ['field' => $sub_condition];
    }
    else {
      // The id directly pre-compiled as it is only a filter.
      if ($field === $this->idKey) {
        if (!is_array($value)) {
          $operator = $operator === '=' ? 'IN' : 'NOT IN';
          $value = [$value];
        }
        // Handle the resource Ids.
        $value = SparqlArg::toResourceUris($value);
        $this->fieldMappings[$field] = self::ID_KEY;
        $this->fieldMappingConditions[] = [
          'field' => self::ID_KEY,
          'value' => $value,
          'operator' => $operator,
        ];
        return $this;
      }
      // If the operator is not '=', it is compiled as a filter, thus, the field
      // name must be added in the 'where' clause as a variable.
      if ($operator !== '=' && !($field instanceof ConditionInterface)) {
        // 'EXISTS' and 'NOT EXISTS' do not need the fields to be bound. They
        // are handled separately.
        if (!in_array($operator, ['EXISTS', 'NOT EXISTS'])) {
          $this->fields[] = $field;
        }
        $this->filters[] = [
          'field' => $field,
          'value' => $value,
          'operator' => $operator,
          'langcode' => $langcode,
        ];
      }
      else {
        $this->conditions[] = [
          'field' => $field,
          'value' => $value,
          'operator' => $operator,
          'langcode' => $langcode,
        ];
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function compile($query) {
    // Fetch all mappings required for the query.
    $this->preCompile($query);
    $filter_fragments = [];

    // Before the compilation, if there are no conditions, add a default
    // condition.
    if (empty($this->conditions)) {
      $this->addConditionFragment(self::ID_KEY . ' ' . $this->typePredicate . ' ' . $this->toVar($this->bundleKey, TRUE));
    }

    foreach ($this->conditions() as $condition) {
      // @todo: Change this to the SparqlCondition interface when it is created.
      if ($condition['field'] instanceof ConditionInterface) {
        $condition['field']->compile($query);
        $condition_fragments[] = $condition['field']->toString();
      }
      else {
        // If it is not a direct triple, it is a filter so a variable is being
        // added for the value.
        if ($condition['operator'] !== '=' && $condition['field'] !== $this->idKey) {
          $this->addConditionFragment(self::ID_KEY . ' ' . $this->fieldMappings[$condition['field']] . ' ' . $this->toVar($condition['field']));
        }
        switch ($condition['operator']) {
          case '=':
            $this->addConditionFragment(self::ID_KEY . ' ' . $this->fieldMappings[$condition['field']] . ' ' . $condition['value']);
            break;

          case 'EXISTS':
          case 'NOT EXISTS':
            $this->addConditionFragment($this->compileExists($condition));
            break;

          case 'LIKE':
          case 'NOT LIKE':
            $this->addConditionFragment($this->compileLike($condition));
            break;

          default:
            $this->addConditionFragment($this->compileFilter($condition));

        }
      }
    }

    // Compile the field mapping filters. All of them are 'IN' filter types.
    foreach ($this->fieldMappingConditions as $condition) {
      $filter_fragments[] = $this->compileFilter($condition);
    }

    // Finally, bring the filters together.
    if (!empty($filter_fragments)) {
      $this->addConditionFragment($this->compileFilters($filter_fragments));
    }

    // Put together everything.
    $this->stringVersion = implode(self::$conjunctionMap[$this->conjunction]['delimeter'], array_unique($this->conditionFragments));
    $this->isCompiled = TRUE;
  }

  /**
   * Adds a condition string to the condition fragments.
   *
   * The encapsulation of the condition according to the conjunction is taking
   * place here.
   *
   * @param string $condition_string
   *   A string version of the condition.
   */
  protected function addConditionFragment($condition_string) {
    $prefix = self::$conjunctionMap[$this->conjunction]['prefix'];
    $suffix = self::$conjunctionMap[$this->conjunction]['suffix'];
    $this->conditionFragments[] = $prefix . $condition_string . $suffix;
  }

  /**
   * Pre compiles the conditions.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The query object.
   */
  protected function preCompile(QueryInterface $query) {
    $this->preCompileConditions($query, $this->conditions);
    $this->preCompileConditions($query, $this->filters);
  }

  /**
   * Map the field names with the corresponding resource IDs.
   *
   * The predicate mapping can not added as a direct filter. It is being
   * loaded from the database. There is no way that in a single request, the
   * same predicate is found with a single and multiple mappings.
   * There is no filter per bundle in the query. That makes it safe to not check
   * on the predicate mappings that are already in the query.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The query class.
   * @param array $conditions
   *   The condition array.
   */
  protected function preCompileConditions(QueryInterface $query, array &$conditions) {
    $entity_type = $query->getEntityType();
    // The triples come before the filter related fields.
    foreach ($conditions as $index => $condition) {
      if ($condition['field'] instanceof ConditionInterface) {
        continue;
      }
      elseif ($condition['field'] === $this->bundleKey) {
        $mappings = [$this->typePredicate];
      }
      else {
        $mappings = $this->mappingHandler->getFieldRdfMapping($entity_type->id(), $condition['field']);
      }
      if (count($mappings) == 1) {
        $this->fieldMappings[$condition['field']] = reset($mappings);
      }
      else {
        if (!isset($this->fieldMappings[$condition['field']])) {
          $this->fieldMappings[$condition['field']] = $this->toVar($condition['field'] . '_predicate');
        }
        // The predicate mapping is not added as a direct filter. It is being
        // loaded by the database. There is no way that in a single request, the
        // same predicate is found with a single and multiple mappings.
        // There is no filter per bundle in the query.
        $this->fieldMappingConditions[] = [
          'field' => $condition['field'] . '_predicate',
          'value' => array_values($mappings),
          'operator' => 'IN',
        ];
      }

      // Finally, handle the case where the field is a reference field.
      $conditions[$index]['value'] = $this->escapeValue($condition['field'], $condition['value']);
    }
  }

  /**
   * Compiles a filter exists condition.
   *
   * @param array $condition
   *   An array that contains the 'field', 'value', 'operator' values.
   *
   * @return string
   *   A condition fragment string.
   */
  protected function compileExists(array $condition) {
    $prefix = self::$filterOperatorMap[$condition['operator']]['prefix'];
    $suffix = self::$filterOperatorMap[$condition['operator']]['suffix'];
    return $prefix . $this->toVar($condition['field']) . $suffix;
  }

  /**
   * Compiles a filter 'LIKE' condition using a regex.
   *
   * @param array $condition
   *   An array that contains the 'field', 'value', 'operator' values.
   *
   * @return string
   *   A condition fragment string.
   */
  protected function compileLike(array $condition) {
    $prefix = self::$filterOperatorMap[$condition['operator']]['prefix'];
    $suffix = self::$filterOperatorMap[$condition['operator']]['suffix'];
    $value = $this->toVar($condition['field']) . ', ' . addslashes($condition['value']);
    return $prefix . $value . $suffix;
  }

  /**
   * Compiles a filter condition.
   *
   * @param array $condition
   *   An array that contains the 'field', 'value', 'operator' values.
   *
   * @return string
   *   A condition fragment string.
   *
   * @throws \Exception
   *    Thrown when a value is an array but a string is expected.
   */
  protected function compileFilter(array $condition) {
    $prefix = self::$filterOperatorMap[$condition['operator']]['prefix'];
    $suffix = self::$filterOperatorMap[$condition['operator']]['suffix'];
    if (is_array($condition['value'])) {
      if (!isset(self::$filterOperatorMap[$condition['operator']]['delimiter'])) {
        throw new \Exception("An array value is not supported for this operator.");
      }
      $condition['value'] = '(' . implode(self::$filterOperatorMap[$condition['operator']]['delimiter'], $condition['value']) . ')';
    }
    $condition['field'] = $this->toVar($condition['field']);
    return $prefix . $condition['field'] . ' ' . $condition['operator'] . ' ' . $condition['value'] . $suffix;
  }

  /**
   * Compiles a filter condition.
   *
   * @param array $filter_fragments
   *   An array of filter strings.
   *
   * @return string
   *   A condition fragment string.
   */
  protected function compileFilters(array $filter_fragments) {
    // The delimeter is always a '&&' because otherwise it would be a separate
    // condition class.
    $delimiter = '&&';
    if (count($filter_fragments) > 1) {
      $compiled_filter = '(' . implode(') ' . $delimiter . '(', $filter_fragments) . ')';
    }
    else {
      $compiled_filter = reset($filter_fragments);
    }

    return 'FILTER (' . $compiled_filter . ')';
  }

  /**
   * Implements \Drupal\Core\Entity\Query\ConditionInterface::exists().
   */
  public function exists($field, $langcode = NULL) {
    $this->condition($field, NULL, 'EXISTS');
  }

  /**
   * Implements \Drupal\Core\Entity\Query\ConditionInterface::notExists().
   */
  public function notExists($field, $langcode = NULL) {
    $this->condition($field, NULL, 'NOT EXISTS');
  }

  /**
   * Prefixes a keyword with a prefix in order to be treated as a variable.
   *
   * @param string $key
   *   The name of the variable.
   * @param bool $blank
   *   Whether or not to be a blank note.
   *
   * @return string
   *   The variable.
   */
  protected function toVar($key, $blank = FALSE) {
    if (strpos($key, '?') === FALSE && strpos($key, '_:') === FALSE) {
      return ($blank ? '_:' : '?') . $key;
    }

    // Do not alter the string if it is already prefixed as a variable.
    return $key;
  }

  /**
   * Check if the field is a resource reference field.
   *
   * This method is merely a helper method to shorten the method call.
   *
   * @param string $field_name
   *   The field machine name.
   * @param string|array $value
   *   A value or an array of values.
   *
   * @return string|array
   *   The altered $value.
   *
   * @todo: This should include better handling and more value format supporting.
   */
  protected function escapeValue($field_name, $value) {
    if (!$this->mappingHandler->fieldIsRdfReference($this->query->getEntityTypeId(), $field_name)) {
      return SparqlArg::literal($value);
    }

    if (!is_array($value)) {
      return SparqlArg::uri($value);
    }

    return SparqlArg::toResourceUris($value);
  }

  /**
   * {@inheritdoc}
   */
  public function isCompiled() {
    return (bool) $this->isCompiled;
  }

  /**
   * {@inheritdoc}
   */
  public function __clone() {}

  /**
   * Returns the string version of the conditions.
   *
   * @return string
   *   The string version of the conditions.
   */
  public function toString() {
    if (!$this->isCompiled()) {
      $this->compile($this->query);
    }

    return $this->stringVersion;
  }

}
