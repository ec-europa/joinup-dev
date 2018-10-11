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
   * Retrieve filter plugin definitions for a specific field.
   *
   * @param \Drupal\search_api\Item\FieldInterface $field
   *   The Search API field.
   *
   * @return mixed[]
   *   An array of plugin definitions (empty array if no definitions were
   *   found). Keys are plugin IDs.
   */
  public function getDefinitionsForField(FieldInterface $field): array;

}
