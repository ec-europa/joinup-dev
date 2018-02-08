<?php

declare(strict_types = 1);

namespace Drupal\joinup_invite\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Invitation type entity, which provides bundles for Invitations.
 *
 * @ConfigEntityType(
 *   id = "invitation_type",
 *   label = @Translation("Invitation type"),
 *   config_prefix = "invitation_type",
 *   bundle_of = "invitation",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class InvitationType extends ConfigEntityBundleBase implements InvitationTypeInterface {

  /**
   * The Invitation type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Invitation type label.
   *
   * @var string
   */
  protected $label;

}
