<?php

namespace Drupal\sparql_entity_storage\EventSubscriber;

/**
 * Provides code reuse for inbound and outbound value subscribers.
 */
trait DateTimeTrait {

  /**
   * Checks if a field is of "timestamp" data type but mapped as date xml type.
   *
   * @param array $mapping_info
   *   The field mapping info.
   *
   * @return bool
   *   True if the conditions applies, false otherwise.
   */
  protected function isTimestampAsDateField(array $mapping_info) {
    return $mapping_info['data_type'] === 'timestamp' && array_key_exists($mapping_info['format'], $this->getDateDataTypes());
  }

  /**
   * Returns the XML date data types and their format for the date() function.
   *
   * @return array
   *   The list of date data types.
   */
  protected function getDateDataTypes() {
    return [
      // \DateTime::ISO8601 is actually not compliant with ISO8601 at all.
      // @see http://php.net/manual/en/class.datetime.php#datetime.constants.iso8601
      'xsd:dateTime' => 'c',
      'xsd:date' => 'Y-m-d',
    ];
  }

}
