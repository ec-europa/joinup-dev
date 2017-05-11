<?php

namespace Drupal\rdf_entity\Entity\Query\Sparql;

use Drupal\Core\Entity\Query\ConditionFundamentals;
use Drupal\Core\Entity\Query\ConditionInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\rdf_entity\RdfGraphHandler;
use Drupal\rdf_entity\RdfFieldHandler;
use EasyRdf\Serialiser\Ntriples;

/**
 * Defines the condition class for the null entity query.
 *
 * @todo: Build a ConditionInterface that extends the ConditionInterface below.
 */
class SparqlCondition extends ConditionFundamentals implements ConditionInterface {

  /**
   * A list of allowed operators for the id and bundle key.
   */
  const ID_BUNDLE_ALLOWED_OPERATORS = ['=', '!=', '<>', 'IN', 'NOT IN'];

  /**
   * The rdf graph handler service object.
   *
   * @var \Drupal\rdf_entity\RdfGraphHandler
   */
  protected $graphHandler;

  /**
   * The rdf mapping handler service object.
   *
   * @var \Drupal\rdf_entity\RdfFieldHandler
   */
  protected $fieldHandler;

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
    'CONTAINS' => ['prefix' => 'FILTER(CONTAINS(', 'suffix' => '))'],
    'STARTS WITH' => ['prefix' => 'FILTER(STRSTARTS(', 'suffix' => '))'],
    'ENDS WITH' => ['prefix' => 'FILTER(STRENDS(', 'suffix' => '))'],
    'LIKE' => ['prefix' => 'FILTER(CONTAINS(', 'suffix' => '))'],
    'NOT LIKE' => ['prefix' => 'FILTER(!CONTAINS(', 'suffix' => '))'],
    'EXISTS' => ['prefix' => 'FILTER EXISTS {', 'suffix' => '}'],
    'NOT EXISTS' => ['prefix' => 'FILTER NOT EXISTS {', 'suffix' => '}'],
    // @todo This is not starts with but contains...
    'STARTS_WITH' => ['prefix' => 'FILTER(regex(', 'suffix' => ', "i"))'],
    '<' => ['prefix' => '', 'suffix' => ''],
    '>' => ['prefix' => '', 'suffix' => ''],
    '>=' => ['prefix' => '', 'suffix' => ''],
    '<=' => ['prefix' => '', 'suffix' => ''],
  ];

  /**
   * The operators that require a default triple pattern to be added.
   *
   * In SPARQL, some of the conditions might need a combination of a pattern and
   * a condition. Below are the operators that need a secondary condition.
   *
   * @var array
   *    An array of operators.
   */
  protected $requiresDefaultPatternOperators = [
    'IN',
    'NOT IN',
    'IS NULL',
    'IS NOT NULL',
    'CONTAINS',
    'LIKE',
    'NOT LIKE',
    'STARTS WITH',
    'ENDS WITH',
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
  protected $needsRecompile = TRUE;

  /**
   * Whether the default triple pattern is required in the query.
   *
   * This will be turned to false if there is at least one condition that does
   * not involve the id.
   *
   * @var bool
   */
  protected $requiresDefaultPattern = TRUE;

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
  public function __construct($conjunction, QueryInterface $query, array $namespaces, RdfGraphHandler $rdf_graph_handler, RdfFieldHandler $rdf_field_handler) {
    $conjunction = strtoupper($conjunction);
    parent::__construct($conjunction, $query, $namespaces);
    $this->graphHandler = $rdf_graph_handler;
    $this->fieldHandler = $rdf_field_handler;
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
   * @todo: handle the lang.
   */
  public function condition($field = NULL, $value = NULL, $operator = NULL, $lang = NULL) {
    if ($this->conjunction == 'OR') {
      $sub_condition = $this->query->andConditionGroup();
      $sub_condition->condition($field, $value, $operator, $lang);
      $this->conditions[] = ['field' => $sub_condition];
      return $this;
    }

    // If the field is a nested condition, simply add it to the list of
    // conditions.
    if ($field instanceof ConditionInterface) {
      $this->conditions[] = ['field' => $field];
      return $this;
    }

    if ($operator === NULL) {
      $operator = '=';
    }

    switch ($field) {
      case $this->bundleKey:
        // If a bundle filter is passed, then there is no need for a default
        // condition.
        $this->requiresDefaultPattern = FALSE;

      case $this->idKey:
        $this->keyCondition($field, $value, $operator);
        break;

      default:
        // In case the field name includes the column, explode it.
        // @see \Drupal\og\MembershipManager::getGroupContentIds
        $field_name_parts = explode('.', $field);
        $field = $field_name_parts[0];
        $column = isset($field_name_parts[1]) ? $field_name_parts[1] : $this->fieldHandler->getFieldMainProperty($this->query->getEntityTypeId(), $field);
        $this->conditions[] = [
          'field' => $field,
          'value' => $value,
          'operator' => $operator,
          'lang' => $lang,
          'column' => $column,
        ];

        if (!in_array($operator, ['EXISTS', 'NOT EXISTS'])) {
          $this->requiresDefaultPattern = FALSE;
        }
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
    if (!in_array($operator, self::ID_BUNDLE_ALLOWED_OPERATORS)) {
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
          'column' => NULL,
          'lang' => NULL,
        ];

        break;

      // Re-write '!=' and '<>' as 'NOT IN' operators as it can be handled
      // in a generic way.
      case '!=':
      case '<>':
        $value = [$value];
        $operator = 'NOT IN';

      case 'NOT IN':
        $this->conditions[] = [
          'field' => $field,
          'value' => $value,
          'operator' => $operator,
          'column' => NULL,
          'lang' => NULL,
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
    // The id and bundle keys do not need to be compiled as they were already
    // handled in the keyCondition.
    $condition_stack = array_filter($condition_stack, function ($condition) {
      return !in_array($condition['field'], [$this->idKey, $this->bundleKey]);
    });

    foreach ($condition_stack as $index => $condition) {
      if ($condition['field'] instanceof ConditionInterface) {
        $condition['field']->compile($query);
      }
      else {
        $mappings = $this->fieldHandler->getFieldPredicates($entity_type->id(), $condition['field'], $condition['column']);
        $field_name = $condition['field'] . '__' . $condition['column'];
        if (count($mappings) === 1) {
          $this->fieldMappings[$field_name] = reset($mappings);
        }
        else {
          if (!isset($this->fieldMappings[$field_name])) {
            $this->fieldMappings[$field_name] = $field_name . '_predicate';
          }
          // The predicate mapping is not added as a direct filter. It is being
          // loaded by the database. There is no way that in a single request,
          // the same predicate is found with a single and multiple mappings.
          // There is no filter per bundle in the query.
          $this->fieldMappingConditions[] = [
            'field' => $condition['field'],
            'column' => $condition['column'],
            'value' => array_values($mappings),
            'operator' => 'IN',
          ];
        }
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

    // There are cases where not a single triple is formed. In these cases, a
    // default condition has to be added in order to retrieve distinct Ids.
    // Also, in case the query is an OR, it should not get the default condition
    // because it will be added in every subquery which has an 'AND' conjuncion.
    if (($this->requiresDefaultPattern && $this->conjunction === 'AND') || empty($this->conditions())) {
      $this->addDefaultTriplePattern();
    }

    // The fieldMappingConditions are added first because they are converted
    // into a 'VALUES' clause which increases performance.
    $this->fieldMappingConditionsToString();

    // Convert the conditions.
    $this->conditionsToString();

    // Put together everything.
    $condition_fragments = array_merge($this->tripleFragments, $this->conditionFragments);
    return implode(self::$conjunctionMap[$this->conjunction]['delimeter'], array_unique($condition_fragments));
  }

  /**
   * Converts the field mapping conditions into string versions.
   */
  private function fieldMappingConditionsToString() {
    foreach ($this->fieldMappingConditions as $condition) {
      $field_name = $condition['field'] . '__' . $condition['column'];
      $field_predicate = $this->fieldMappings[$field_name];
      $this->addConditionFragment(self::ID_KEY . ' ' . $this->escapePredicate($field_predicate) . ' ' . $this->toVar($field_name));
      $condition['value'] = SparqlArg::toResourceUris($condition['value']);
      $condition['field'] = $field_predicate;
      $this->addConditionFragment($this->compileValuesFilter($condition));
    }
  }

  /**
   * Converts the conditions into string versions.
   */
  private function conditionsToString() {
    $filter_fragments = [];

    foreach ($this->conditions as $condition) {
      if ($condition['field'] instanceof ConditionInterface) {
        // Get the string version of a nested condition.
        $this->addConditionFragment($condition['field']->toString());
        continue;
      }

      // The id key only needs a conversion on the field to its corresponding
      // variable version.
      if ($condition['field'] === $this->idKey) {
        $field_name = $this->fieldMappings[$condition['field']];
        $predicate = $this->fieldMappings[$condition['field']];
      }
      // The bundle needs special handle as it might get a filter both in the
      // predicate and the value.
      elseif ($condition['field'] === $this->bundleKey) {
        $this->compileBundleCondition($condition);
        // This will be '{$bundle_key}' as it is always an 'IN' or a 'NOT IN'
        // clause.
        $field_name = $condition['field'];
        $predicate = $this->fieldMappings[$condition['field']];
      }
      // Implement the appropriate conversions. If the field has a single
      // mapping, convert it into a triple. If field predicate is having more
      // than one values, get the predicate variable and set it in the triple.
      else {
        $field_name = $condition['field'] . '__' . $condition['column'];
        $predicate = $this->fieldMappings[$field_name];
        $langcode = $this->getLangCode($condition['field'], $condition['column'], $condition['lang']);
        // In case the operator is not '=', add a support triple pattern.
        if (in_array($condition['operator'], $this->requiresDefaultPatternOperators) && isset($this->fieldMappings[$field_name])) {
          $this->addConditionFragment(self::ID_KEY . ' ' . $this->escapePredicate($this->fieldMappings[$field_name]) . ' ' . $this->toVar($field_name));
        }
      }

      $condition['value'] = $this->escapeValue($condition['field'], $condition['value'], $condition['column'], $condition['lang']);
      $condition['field'] = $field_name;
      switch ($condition['operator']) {
        case '=':
          // The id is not going to end with an '=' operator so it is safe to
          // use the $predicate variable.
          $this->tripleFragments[] = self::ID_KEY . ' ' . $this->escapePredicate($predicate) . ' ' . $condition['value'];
          break;

        case 'EXISTS':
        case 'NOT EXISTS':
          $this->addConditionFragment($this->compileExists($condition));
          break;

        case 'CONTAINS':
        case 'LIKE':
        case 'NOT LIKE':
        case 'STARTS WITH':
        case 'ENDS WITH':
          $this->addConditionFragment($this->compileLike($condition));
          if (!empty($langcode)) {
            $this->addConditionFragment("FILTER(lang({$this->toVar($condition['field'])}) = '{$langcode}')");
          }
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
  protected function addDefaultTriplePattern() {
    $this->compileBundleCondition(['field' => $this->bundleKey]);
  }

  /**
   * Adds a default bundle condition to the condition class.
   *
   * If the type predicates are more than one, this will result in
   * { ?entity ?{$bundle_key}_predicate ?{$bundle_key} .
   * VALUES ?{$bundle_key}_predicate { ... }}.
   *
   * If there is only one type predicate then it is added directly like
   * { ?entity ?<bundle_predicate> ?{$bundle_key} }
   *
   * This method simply adds the mapping condition for the bundle if needed
   * otherwise, it simply adds a needed triple.
   *
   * @todo: This could work generically but currently the term storage has
   * two possible predicates. This can be removed when we will be left with one
   * predicate for the term storage.
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
      $this->addConditionFragment(self::ID_KEY . ' ' . $this->escapePredicate($this->fieldMappings[$condition['field']]) . ' ' . $this->toVar($condition['field']));
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
    return $prefix . self::ID_KEY . ' ' . $this->escapePredicate($this->fieldMappings[$condition['field']]) . ' ' . $this->toVar($condition['field']) . $suffix;
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
    $value = 'str(' . $this->toVar($condition['field']) . '), str(' . $condition['value'] . ')';
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
   * Calculates the langcode of the field if one exists.
   *
   * @param string $field
   *   The field name.
   * @param string $column
   *   The column name.
   * @param string $default_lang
   *   A default langcode to return.
   *
   * @return string
   *   The langcode to be used.
   */
  protected function getLangCode($field, $column = NULL, $default_lang = NULL) {
    $format = $this->fieldHandler->getFieldFormat($this->query->getEntityTypeId(), $field, $column);
    $format = reset($format);
    if ($format !== RdfFieldHandler::TRANSLATABLE_LITERAL) {
      return FALSE;
    }

    $non_languages = [
      LanguageInterface::LANGCODE_NOT_SPECIFIED,
      LanguageInterface::LANGCODE_DEFAULT,
      LanguageInterface::LANGCODE_NOT_APPLICABLE,
      LanguageInterface::LANGCODE_SITE_DEFAULT,
      LanguageInterface::LANGCODE_SYSTEM,
    ];

    if (empty($default_lang) || in_array($default_lang, $non_languages)) {
      return \Drupal::languageManager()->getCurrentLanguage()->getId();
    }

    return $default_lang;
  }

  /**
   * Implements \Drupal\Core\Entity\Query\ConditionInterface::exists().
   */
  public function exists($field, $lang = NULL) {
    return $this->condition($field, NULL, 'EXISTS');
  }

  /**
   * Implements \Drupal\Core\Entity\Query\ConditionInterface::notExists().
   */
  public function notExists($field, $lang = NULL) {
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
    // Deal with field.property as dots are not allowed in var names.
    $key = str_replace('.', '_', $key);
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
   * @param string $field
   *   The field name.
   * @param mixed $value
   *   The value.
   * @param string $column
   *   The column name.
   * @param string|null $lang
   *   The langcode of the value.
   *
   * @return string|array
   *   The altered $value.
   */
  protected function escapeValue($field, $value, $column = NULL, $lang = NULL) {
    if (empty($value)) {
      return $value;
    }
    // If the field name is the id, escape the value. It has been already
    // converted and the value is an array.
    if ($field === $this->idKey) {
      return SparqlArg::toResourceUris($value);
    }

    // If the field name is the bundle key, convert the values to their
    // corresponding mappings. The value is also an array.
    elseif ($field === $this->bundleKey) {
      $value = $this->fieldHandler->bundlesToUris($this->query->getEntityTypeId(), $value, TRUE);
      return $value;
    }

    $serializer = new Ntriples();
    $lang = $this->getLangCode($field, $column, $lang);
    if (is_array($value)) {
      foreach ($value as $i => $v) {
        $outbound_value = $this->fieldHandler->getOutboundValue($this->query->getEntityTypeId(), $field, $v, $lang, $column);
        $value[$i] = $serializer->serialiseValue($outbound_value);
      }
    }
    else {
      $outbound_value = $this->fieldHandler->getOutboundValue($this->query->getEntityTypeId(), $field, $value, $lang, $column);
      $value = $serializer->serialiseValue($outbound_value);
    }
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
