<?php

declare(strict_types = 1);

namespace Drupal\solution\Cache\Context;

use Drupal\joinup_group\Cache\Context\GroupOwnerCacheContext;

/**
 * Defines a cache context service for solution owners.
 *
 * This cache context should be used in any render elements that have different
 * content when shown to a solution owner.
 *
 * Example use case: the solution overview has an additional "My solutions"
 * facet that is shown only to solution owners.
 *
 * This is similar to OgRoleCacheContext but is much less granular, since we
 * only have a small number of solution owners as compared to members with
 * other roles.
 *
 * Since some users might own many solution the context key is presented as a
 * hashed value.
 *
 * Cache context ID: 'solution_owner'
 */
class SolutionOwnerCacheContext extends GroupOwnerCacheContext {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Solution owner');
  }

  /**
   * An array of OG role IDs that identify solution owners.
   *
   * @var string[]
   */
  protected $roleIds = [
    'rdf_entity-solution-administrator',
  ];

}
