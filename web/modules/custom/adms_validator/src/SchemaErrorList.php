<?php

declare(strict_types = 1);

namespace Drupal\adms_validator;

use EasyRdf\Sparql\Result;

/**
 * A collection of Schema Errors.
 */
class SchemaErrorList {

  protected $errors = [];

  /**
   * Constructs a list of SchemaErrors from a query result.
   *
   * @param \EasyRdf\Sparql\Result $result
   *   The result of the validation query.
   */
  public function __construct(Result $result) {
    foreach ($result as $error) {
      $this->errors[] = new SchemaError($error);
    }
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
        t('Class name'),
        t('Message'),
        t('Object'),
        t('Predicate'),
        t('Rule description'),
        t('Rule ID'),
        t('Rule severity'),
        t('Subject'),
      ],
      '#rows' => $this->toRows(),
      '#empty' => t('No errors.'),
    ];
  }

}
