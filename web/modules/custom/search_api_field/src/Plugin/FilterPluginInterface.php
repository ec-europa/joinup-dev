<?php

declare(strict_types = 1);

namespace Drupal\search_api_field\Plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\search_api\Query\ConditionSetInterface;

/**
 * Interface for Search API Field filters plugins.
 */
interface FilterPluginInterface extends PluginFormInterface, ConfigurableInterface {

  /**
   * Applies the filter configuration to the search query.
   *
   * @param \Drupal\search_api\Query\ConditionSetInterface $condition
   *   The search query object or a condition.
   */
  public function applyFilter(ConditionSetInterface $condition): void;

}
