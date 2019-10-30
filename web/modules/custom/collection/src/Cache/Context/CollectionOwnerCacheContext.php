<?php

namespace Drupal\collection\Cache\Context;

use Drupal\joinup_core\Cache\Context\GroupOwnerCacheContext;

/**
 * Defines a cache context service for collection owners.
 *
 * This cache context should be used in any render elements that have different
 * content when shown to a collection owner.
 *
 * Example use case: the collection overview has an additional "My collections"
 * facet that is shown only to collection owners.
 *
 * This is similar to OgRoleCacheContext but is much less granular, since we
 * only have a small number of collection owners as compared to members with
 * other roles.
 *
 * Since some users might own many collections the context key is presented as a
 * hashed value.
 *
 * Cache context ID: 'collection_owner'
 */
class CollectionOwnerCacheContext extends GroupOwnerCacheContext {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Collection owner');
  }

  /**
   * An array of OG role IDs that identify collection owners.
   *
   * @var string[]
   */
  protected $roleIds = [
    'rdf_entity-collection-administrator',
  ];

}
