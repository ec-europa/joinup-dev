<?php

declare(strict_types = 1);

namespace Drupal\joinup_core\Logger\Processor;

/**
 * Logs the error universal unique ID.
 */
class ErrorPageErrorUuidProcessor {

  /**
   * {@inheritdoc}
   */
  public function __invoke(array $record): array {
    if (!empty($record['context']['@uuid'])) {
      $record['extra']['uuid'] = $record['context']['@uuid'];
    }
    return $record;
  }

}
