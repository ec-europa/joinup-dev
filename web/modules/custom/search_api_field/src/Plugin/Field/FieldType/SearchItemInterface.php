<?php

declare(strict_types = 1);

namespace Drupal\search_api_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemInterface;

/**
 * Interface for Search API Field field items.
 */
interface SearchItemInterface extends FieldItemInterface {

  /**
   * Returns the number of search results that are shown per page.
   *
   * @return int
   *   The number of search results.
   */
  public function getLimit(): int;

  /**
   * Sets the number of search results that are shown per page.
   *
   * @param int $limit
   *   The number of search results.
   *
   * @return \Drupal\search_api_field\Plugin\Field\FieldType\SearchItemInterface
   *   The field item, for chaining method calls.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *   Thrown when the complex data structure that will contain the limit
   *   property is not set and can unset and can not be created.
   */
  public function setLimit(int $limit): self;

}
