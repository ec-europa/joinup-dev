<?php

declare(strict_types = 1);

namespace Drupal\search_api_field\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\search_api\Query\ConditionSetInterface;

/**
 * Interface definition for search api field filters plugins.
 */
interface FilterPluginInterface extends PluginFormInterface, ConfigurablePluginInterface {

  /**
   * Applies the filter configuration to the search query.
   *
   * @param \Drupal\search_api\Query\ConditionSetInterface $condition
   *   The search query object or a condition.
   */
  public function applyFilter(ConditionSetInterface $condition): void;

}
