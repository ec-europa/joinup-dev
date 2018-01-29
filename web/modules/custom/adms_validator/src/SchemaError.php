<?php

declare(strict_types = 1);

namespace Drupal\adms_validator;

/**
 * A value object containing an individual schema violation.
 */
class SchemaError {

  protected $className;

  protected $message;

  protected $object;

  protected $predicate;

  protected $ruleDescription;

  protected $ruleID;

  protected $ruleSeverity;

  protected $subject;

  /**
   * Constructs a SchemaError object from a database result row.
   *
   * @param \stdClass $record
   *   A SPARQL result row.
   */
  public function __construct(\stdClass $record) {
    if (!empty($record->Class_Name)) {
      $this->className = strval($record->Class_Name);
    }
    if (!empty($record->Message)) {
      $this->message = strval($record->Message);
    }
    if (!empty($record->Object)) {
      $this->object = strval($record->Object);
    }
    if (!empty($record->Predicate)) {
      $this->predicate = strval($record->Predicate);
    }
    if (!empty($record->Rule_Description)) {
      $this->ruleDescription = strval($record->Rule_Description);
    }
    if (!empty($record->Rule_ID)) {
      $this->ruleID = strval($record->Rule_ID);
    }
    if (!empty($record->Rule_Severity)) {
      $this->ruleSeverity = strval($record->Rule_Severity);
    }
    if (!empty($record->Subject)) {
      $this->subject = strval($record->Subject);
    }
  }

}
