<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\og\OgMembershipInterface;

/**
 * Interface for entities that are groups.
 *
 * This comprises collections and solutions.
 */
interface GroupInterface extends ContentEntityInterface {

  /**
   * Returns the given user's membership for this group entity.
   *
   * @param int $uid
   *   The ID of the user for which to return the membership.
   * @param array $states
   *   (optional) Array with the states to return. Defaults to only returning
   *   active memberships. In order to retrieve all memberships regardless of
   *   state, pass `OgMembershipInterface::ALL_STATES`.
   *
   * @return \Drupal\og\OgMembershipInterface|null
   *   The OgMembership entity, or NULL if the user with the given ID is not a
   *   member.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown when the OG Membership entity type definition is invalid.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown when the OG module is not enabled.
   */
  public function getMembership(int $uid, array $states = [OgMembershipInterface::STATE_ACTIVE]): ?OgMembershipInterface;

}
