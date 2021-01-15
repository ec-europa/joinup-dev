<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Cache\Context;

use Drupal\og\Cache\Context\OgRoleCacheContext;

/**
 * Defines a cache context service for group owners.
 *
 * This cache context should be used in any render elements that have different
 * content when shown to a group owner.
 *
 * Cache context ID: 'joinup_group_owner'
 */
class GroupOwnerCacheContext extends OgRoleCacheContext {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Group owner');
  }

  /**
   * An array of OG role IDs that identify group owners.
   *
   * @var string[]
   */
  protected $roleIds = [
    'rdf_entity-collection-administrator',
    'rdf_entity-solution-administrator',
  ];

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    // Due to cacheability metadata bubbling this can be called often. Only
    // compute the hash once.
    if (empty($this->hashes[$this->user->id()])) {
      $group_ids = $this->membershipManager->getUserGroupIdsByRoleIds($this->user->id(), $this->roleIds);

      // Discard the parent array, we know that groups are RDF entities.
      $group_ids = $group_ids['rdf_entity'] ?? [];

      // Sort the groups, so that the same key can be generated, even if the
      // groups were returned in a different order.
      sort($group_ids);

      // If the user is not a member of any groups, return a unique key.
      $this->hashes[$this->user->id()] = empty($group_ids) ? self::NO_CONTEXT : $this->hash(serialize($group_ids));
    }

    return $this->hashes[$this->user->id()];
  }

}
