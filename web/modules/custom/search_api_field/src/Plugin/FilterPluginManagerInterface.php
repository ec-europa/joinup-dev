<?php

declare(strict_types = 1);

namespace Drupal\search_api_field\Plugin;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\search_api\Item\FieldInterface;

/**
 * Defines an interface for the search api field filter plugin manager.
 */
interface FilterPluginManagerInterface extends PluginManagerInterface {

  /**
   * Retrieve a filter plugin definition for a specific field.
   *
   * @param \Drupal\search_api\Item\FieldInterface $field
   *   The Search API field.
   *
   * @return string|null
   *   The plugin ID if one is found. Null otherwise.
   */
  public function getDefinitionForField(FieldInterface $field);

}
