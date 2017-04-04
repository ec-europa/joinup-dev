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
    'IN' => ['delimiter' => ' ', 'prefix' => '', 'suffix' => ''],
    'NOT IN' => ['delimiter' => ', ', 'prefix' => '', 'suffix' => ''],
    'IS NULL' => ['use_value' => FALSE],
    'IS NOT NULL' => ['use_value' => FALSE],
    'CONTAINS' => ['prefix' => 'FILTER(regex(', 'suffix' => ', "i"))'],
    'LIKE' => ['prefix' => 'FILTER(regex(', 'suffix' => ', "i"))'],
    'NOT LIKE' => ['prefix' => 'FILTER(!regex(', 'suffix' => ', "i"))'],
    'EXISTS' => ['prefix' => 'FILTER EXISTS {', 'suffix' => '}'],
    'NOT EXISTS' => ['prefix' => 'FILTER NOT EXISTS {', 'suffix' => '}'],
    '<' => ['prefix' => '', 'suffix' => ''],
    '>' => ['prefix' => '', 'suffix' => ''],
    '>=' => ['prefix' => '', 'suffix' => ''],
    '<=' => ['prefix' => '', 'suffix' => ''],
  ];

  /**
   * The operators that require a default triple to be added.
   *
   * In SPARQL, some of the SQL conditions might need more than one conditions
   * combined. Below are the operators that need a secondary condition.
   *
   * @var array
   *    An array of operators.
   */
  protected $requiresTriple = [
    'IN',
    'NOT IN',
    'IS NULL',
    'IS NOT NULL',
    'CONTAINS',
    'LIKE',
    'NOT LIKE',
    '<',
    '>',
    '<=',
    '>=',
    '!=',
    '<>',
  ];

  /**
   * Whether the conditions have been changed.
   *
   * TRUE if the condition has been changed since the last compile.
   * FALSE if the condition has been compiled and not changed.
   *
   * @var bool
   */
  protected $changed = TRUE;

  /**
   * Whether the conditions do not have a triple.
   *
   * This will be turned to false if there is at least one condition that does
   * not involve the id.
   *
   * @var bool
   */
  protected $needsDefault = TRUE;

  /**
   * The default bundle predicate.
   *
   * @var array
   */
  protected $typePredicate = '<http://www.w3.org/1999/02/22-rdf-syntax-ns#type>';

  /**
   * An array of triples in their string version.
   *
   * These are mainly fragments of the '=' operator because they can create
   * triples that will greatly reduce the results.
   *
   * @var string[]
   */
  protected $tripleFragments = [];

  /**
   * An array of conditions in their string version.
   *
   * These are formed during the compilation phase.
   *
   * @var string[]
   */
  protected $conditionFragments = [];

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
    $this->typePredicate = $query->getEntityStorage()->bundlePredicate();
    $this->bundleKey = $query->getEntityType()->getKey('bundle');
    $this->idKey = $query->getEntityType()->getKey('id');
    $this->labelKey = $query->getEntityType()->getKey('label');
    // Initialize variable to avoid warnings;.
    $this->fieldMappingConditions = [];
    $this->fieldMappings = [
      $this->idKey => self::ID_KEY,
      $this->bundleKey => count($this->typePredicate) === 1 ? reset($this->typePredicate) : $this->toVar($this->bundleKey . '_predicate'),
    ];
  }

  /**
   * A list of properties regarding the query conjunction.
   *
   * @var array
   */
  protected static $conjunctionMap = [
    'AND' => ['delimeter' => " .\n", 'prefix' => '', 'suffix' => ''],
    'OR' => ['delimeter' => " UNION\n", 'prefix' => '{ ', 'suffix' => ' }'],
  ];

  /**
   * {@inheritdoc}
   *
   * @todo: handle the langcode.
   */
  public function condition($field = NULL, $value = NULL, $operator = NULL, $langcode = NULL) {
    // In case the field name includes the column, explode it.
    // @see \Drupal\og\MembershipManager::getGroupContentIds
    $field_name_parts = explode('.', $field);
    $field = reset($field_name_parts);
    if ($this->conjunction == 'OR') {
      $sub_condition = $this->query->andConditionGroup();
      $sub_condition->condition($field, $value, $operator, $langcode);
      $this->conditions[] = ['field' => $sub_condition];
      return $this;
    }

    if ($operator === NULL) {
      $operator = '=';
    }

    switch ($field) {
      case $this->bundleKey:
        // If a bundle filter is passed, then there is no need for a default
        // condition.
        $this->needsDefault = FALSE;

      case $this->idKey:
        $this->keyCondition($field, $value, $operator);
        break;

      default:
        $this->needsDefault = FALSE;
        $this->conditions[] = [
          'field' => $field,
          'value' => $value,
          'operator' => $operator,
          'langcode' => $langcode,
        ];
    }

    return $this;
  }

  /**
   * Handle the id and bundle keys.
   *
   * @param string $field
   *   The field name. Should be either the id or the bundle key.
   * @param string|array $value
   *   A string or an array of strings.
   * @param string $operator
   *   The operator.
   *
   * @return \Drupal\Core\Entity\Query\ConditionInterface
   *   The current object.
   *
   * @throws \Exception
   *    Thrown if the value is NULL or the operator is not allowed.
   */
  public function keyCondition($field, $value, $operator) {
    // @todo: Add support for loadMultiple with empty Id (load all).
    if ($value == NULL) {
      throw new \Exception('The value cannot be NULL for conditions related to the Id and bundle keys.');
    }
    if (!in_array($operator, ['=', '!=', '<>', 'IN', 'NOT IN'])) {
      throw new \Exception("Only '=', '!=', '<>', 'IN', 'NOT IN' operators are allowed for the Id and bundle keys.");
    }

    switch ($operator) {
      case '=':
        $value = [$value];
        $operator = 'IN';

      case 'IN':
        $this->conditions[] = [
          'field' => $field,
          'value' => $value,
          'operator' => $operator,
        ];

        break;

      case '!=':
      case '<>':
        $value = [$value];
        $operator = 'NOT IN';

      case 'NOT IN':
        $this->conditions[] = [
          'field' => $field,
          'value' => $value,
          'operator' => $operator,
        ];
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * Map the field names with the corresponding resource IDs.
   * The predicate mapping can not added as a direct filter. It is being
   * loaded from the database. There is no way that in a single request, the
   * same predicate is found with a single and multiple mappings.
   * There is no filter per bundle in the query. That makes it safe to not check
   * on the predicate mappings that are already in the query.
   */
  public function compile($query) {
    $entity_type = $query->getEntityType();
    $condition_stack = array_merge($this->conditions, $this->fieldMappingConditions);
    foreach ($condition_stack as $index => $condition) {
      if (in_array($condition['field'], [$this->idKey, $this->bundleKey])) {
        continue;
      }
      if ($condition['field'] instanceof ConditionInterface) {
        $condition['field']->compile($query);
        continue;
      }
      // The id and bundle are set in the constructor.
      else {
        $mappings = $this->mappingHandler->getFieldRdfMapping($entity_type->id(), $condition['field']);
      }

      // In case multiple bundles define the same resource id for the same
      // predicate, remove the duplicates.
      $mappings = array_unique($mappings);
      if (count($mappings) === 1) {
        $this->fieldMappings[$condition['field']] = reset($mappings);
      }
      else {
        if (!isset($this->fieldMappings[$condition['field']])) {
          $this->fieldMappings[$condition['field']] = $condition['field'] . '_predicate';
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
    }
  }

  /**
   * Returns the string version of the conditions.
   *
   * @return string
   *   The string version of the conditions.
   */
  public function toString() {
    // In case of re-compiling, remove previous fragments. This will ensure that
    // not previous duplicates or leftovers remain.
    $this->conditionFragments = [];
    $filter_fragments = [];

    if ($this->needsDefault) {
      $this->addDefaultCondition();
    }

    // The fieldMappingConditions are added first because they are converted
    // into a 'VALUES' clause which increases performance.
    $condition_stack = array_merge($this->fieldMappingConditions, $this->conditions);
    foreach ($condition_stack as $condition) {
      if ($condition['field'] instanceof ConditionInterface) {
        $this->addConditionFragment($condition['field']->toString());
        continue;
      }
      elseif ($condition['field'] === $this->idKey) {
        $condition['field'] = $this->fieldMappings[$condition['field']];
      }
      elseif ($condition['field'] === $this->bundleKey) {
        $this->compileBundleCondition($condition);
      }
      elseif (in_array($condition['operator'], $this->requiresTriple) && isset($this->fieldMappings[$condition['field']])) {
        $this->addConditionFragment(self::ID_KEY . ' ' . $this->escapePredicate($this->fieldMappings[$condition['field']]) . ' ' . $this->toVar($condition['field']));
      }

      // For the field mappings that require a filter, the $condition['field']
      // parameter is set to '<field_name>_predicate'. Reverse search it from
      // the mappings.
      if ($field_name = array_search($condition['field'], $this->fieldMappings)) {
        $condition['value'] = SparqlArg::toResourceUris($condition['value']);
      }
      else {
        $condition['value'] = $this->escapeValue($condition['field'], $condition['value']);
      }

      switch ($condition['operator']) {
        case '=':
          $this->tripleFragments[] = self::ID_KEY . ' ' . $this->escapePredicate($this->fieldMappings[$condition['field']]) . ' ' . $condition['value'];
          break;

        case 'EXISTS':
        case 'NOT EXISTS':
          $this->addConditionFragment($this->compileExists($condition));
          break;

        case 'CONTAINS':
        case 'LIKE':
        case 'NOT LIKE':
          $this->addConditionFragment($this->compileLike($condition));
          // Set the default language to the fields.
          // @todo: Remove this when proper language handling is set up.
          $this->addConditionFragment("FILTER(lang({$this->toVar($condition['field'])}) = 'en')");
          break;

        case 'IN':
          $this->addConditionFragment($this->compileValuesFilter($condition));
          break;

        default:
          $filter_fragments[] = $this->compileFilter($condition);

      }
    }

    // Finally, bring the filters together.
    if (!empty($filter_fragments)) {
      $this->addConditionFragment($this->compileFilters($filter_fragments));
    }

    // Put together everything.
    $condition_fragments = array_merge($this->tripleFragments, $this->conditionFragments);
    $this->stringVersion = implode(self::$conjunctionMap[$this->conjunction]['delimeter'], array_unique($condition_fragments));
    return $this->stringVersion;
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
   * Adds a default condition to the condition class.
   */
  protected function addDefaultCondition() {
    if (count($this->typePredicate) > 1) {
      $field = $this->toVar($this->bundleKey . '_predicate');
      $this->addConditionFragment(self::ID_KEY . ' ' . $field . ' ' . $this->toVar($this->bundleKey, TRUE));
      $this->addConditionFragment($this->compileValuesFilter([
        'field' => $this->escapePredicate($this->fieldMappings[$this->bundleKey]),
        'value' => SparqlArg::toResourceUris($this->typePredicate),
        'operator' => 'IN',
      ]));
      $this->fieldMappings[$this->bundleKey] = $field;
    }
    else {
      $predicate = $this->escapePredicate(reset($this->typePredicate));
      $this->addConditionFragment(self::ID_KEY . ' ' . $predicate . ' ' . $this->toVar($this->bundleKey, TRUE));
      $this->fieldMappings[$this->bundleKey] = $predicate;
    }
  }

  /**
   * Adds a default condition to the condition class.
   */
  protected function compileBundleCondition($condition) {
    if (count($this->typePredicate) > 1) {
      $this->addConditionFragment(self::ID_KEY . ' ' . $this->escapePredicate($this->fieldMappings[$condition['field']]) . ' ' . $this->toVar($condition['field']));
      $this->addConditionFragment($this->compileValuesFilter([
        'field' => $this->escapePredicate($this->fieldMappings[$condition['field']]),
        'value' => SparqlArg::toResourceUris($this->typePredicate),
        'operator' => 'IN',
      ]));
    }
    else {
      $this->addConditionFragment(self::ID_KEY . ' ' . $this->escapePredicate($this->fieldMappings[$condition['field']]) . ' ' . $this->toVar($this->bundleKey));
      $this->fieldMappings[$this->bundleKey] = reset($this->typePredicate);
    }
  }

  /**
   * Compiles a filter exists (or not exists) condition.
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
    return $prefix . self::ID_KEY . ' ' . $this->escapePredicate($this->fieldMappings[$condition['field']]) . ' ' . $this->toVar($condition['field'], TRUE) . $suffix;
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
    $value = $this->toVar($condition['field']) . ', ' . $condition['value'];
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
   * Compiles an 'IN' condition as a SPARQL 'VALUES'.
   *
   * 'VALUES' is preferred over 'FILTER IN' for performance.
   * This should only be called for subject and predicate filter as it considers
   * values to be resources.
   *
   * @param array $condition
   *   The condition array.
   *
   * @return string
   *   The string version of the condition.
   */
  protected function compileValuesFilter(array $condition) {
    if (is_string($condition['value'])) {
      $value = [$condition['value']];
    }
    else {
      $value = $condition['value'];
    }
    return 'VALUES ' . $this->toVar($condition['field']) . ' {' . implode(' ', $value) . '}';
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
    // The delimiter is always a '&&' because otherwise it would be a separate
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
   * Escape the predicate.
   *
   * If the value is a uri, convert it to a resource. If it is not a uri,
   * convert it to a variable.
   *
   * @param string $field_name
   *   The field machine name.
   *
   * @return string
   *   The altered $value.
   */
  protected function escapePredicate($field_name) {
    if (SparqlArg::isValidResource($field_name)) {
      return SparqlArg::uri($field_name);
    }
    return $this->toVar($field_name);
  }

  /**
   * Handle the value according to their type.
   *
   * @param string $field_name
   *   The field machine name.
   * @param string|array $value
   *   A value or an array of values.
   *
   * @return string|array
   *   The altered $value.
   */
  protected function escapeValue($field_name, $value) {
    // If the field name is the id, escape the value. It has been already
    // converted and the value is an array.
    if ($field_name === $this->fieldMappings[$this->idKey]) {
      return SparqlArg::toResourceUris($value);
    }

    // If the field name is the bundle key, convert the values to their
    // corresponding mappings. The value is also an array.
    if ($field_name === $this->bundleKey) {
      $this->mappingHandler->bundlesToUris($this->query->getEntityTypeId(), $value, TRUE);
      return $value;
    }

    // @todo: The files will not work here probably.
    if ($this->mappingHandler->fieldIsRdfReference($this->query->getEntityTypeId(), $field_name)) {
      return is_array($value) ? SparqlArg::toResourceUris($value) : SparqlArg::uri($value);
    }

    // @todo: Handle more formats. For now, escape as literal every other case.
    $value = is_array($value) ? SparqlArg::literals($value) : SparqlArg::literal($value);
    return $value;
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

}
