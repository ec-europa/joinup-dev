<?php

declare(strict_types = 1);

namespace Drupal\adms_validator;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface;
use EasyRdf\Sparql\Result;

/**
 * A collection of Schema Errors.
 */
class AdmsValidationResult {

  use StringTranslationTrait;

  protected $errors = [];

  /**
   * Constructs a list of SchemaErrors from a query result.
   *
   * @param \EasyRdf\Sparql\Result $result
   *   The result of the validation query.
   * @param string $graph_uri
   *   The graph URI.
   * @param \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface $sparql
   *   The SPARQL endpoint.
   *
   * @todo Remove $graph_uri, $sparql params in ISAICP-4296.
   * @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-4296
   */
  public function __construct(Result $result, $graph_uri, ConnectionInterface $sparql) {
    $skipped_rules = [100, 101, 102, 103];
    foreach ($result as $error) {
      // @todo Remove this hack in ISAICP-4296.
      // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-4296
      if (!in_array($error->Rule_ID->getValue(), $skipped_rules)) {
        $this->errors[] = new SchemaError($error);
      }
    }
    $this->applyRules100To103($graph_uri, $sparql);
  }

  /**
   * Casts the schema errors to an array for rendering.
   *
   * @return array
   *   Renderable data.
   */
  public function toRows() : array {
    return array_map(function ($error) {
      return (array) $error;
    }, $this->errors);
  }

  /**
   * The amount of errors in the list.
   *
   * @return int
   *   Error count.
   */
  public function errorCount() : int {
    return count($this->errors);
  }

  /**
   * Returns TRUE if the validation is successful.
   *
   * @return bool
   *   TRUE if the validation is successful.
   */
  public function isSuccessful(): bool {
    return $this->errorCount() === 0;
  }

  /**
   * Returns the errors in a table as a render array.
   *
   * @return array
   *   A table render array containing the list of errors.
   */
  public function toTable(): array {
    return [
      '#theme' => 'table',
      '#header' => [
        $this->t('Class name'),
        $this->t('Message'),
        $this->t('Object'),
        $this->t('Predicate'),
        $this->t('Rule description'),
        $this->t('Rule ID'),
        $this->t('Rule severity'),
        $this->t('Subject'),
      ],
      '#rows' => $this->toRows(),
      '#empty' => $this->t('No errors.'),
    ];
  }

  /**
   * Fix rules 100, 101, 102, 103.
   *
   * @param string $graph_uri
   *   The graph URI.
   * @param \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface $sparql
   *   The SPARQL endpoint.
   *
   * @todo Remove this hack in ISAICP-4296.
   * @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-4296
   */
  protected function applyRules100To103($graph_uri, ConnectionInterface $sparql) {
    $rules = [
      100 => [
        'prefix' => 'dcat:Dataset',
        'uri' => 'http://www.w3.org/ns/dcat#Dataset',
      ],
      101 => [
        'prefix' => 'skos:Concept',
        'uri' => 'http://www.w3.org/2004/02/skos/core#Concept',
      ],
      102 => [
        'prefix' => 'v:Kind',
        'uri' => 'http://www.w3.org/2006/vcard/ns#Kind',
      ],
      103 => [
        'prefix' => 'foaf:Agent',
        'uri' => 'http://xmlns.com/foaf/0.1/Agent',
      ],
    ];

    foreach ($rules as $rule_id => $rule) {

      $query = "ASK WHERE { GRAPH <$graph_uri> { ?s a <{$rule['uri']}> } }";
      if (!$sparql->query($query)->isTrue()) {

        $error = (object) [
          'Class_Name' => $rule['prefix'],
          'Message' => "The mandatory class {$rule['prefix']} does not exist.",
          'Rule_Description' => "{$rule['prefix']} does not exist.",
          'Rule_ID' => $rule_id,
          'Rule_Severity' => 'error',
        ];
        $this->errors[] = new SchemaError($error);
      }
    }
  }

}
