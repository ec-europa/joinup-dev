<?php

namespace Drupal\asset_distribution\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;

/**
 * Composite constraint for the 'download_event' entity.
 *
 * @Constraint(
 *   id = "DownloadEvent",
 *   label = @Translation("Download event constraint", context = "Validation"),
 *   type = "entity"
 * )
 */
class DownloadEvent extends CompositeConstraintBase {

  /**
   * User or mail validation error message.
   *
   * @var string
   */
  public $userOrMail = "Inconsistent data: Either a valid user ID or a mail address should be provided.";

  /**
   * {@inheritdoc}
   */
  public function coversFields() {
    return ['mail', 'uid'];
  }

}
