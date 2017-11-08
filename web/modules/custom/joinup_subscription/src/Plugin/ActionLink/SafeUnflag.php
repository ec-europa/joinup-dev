<?php

namespace Drupal\joinup_subscription\Plugin\ActionLink;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\flag\ActionLink\ActionLinkTypeBase;
use Drupal\flag\FlagInterface;

/**
 * Provides a flag simple link for flagging and a confirm form for unflagging.
 *
 * @ActionLinkType(
 *   id = "safe_unflag",
 *   label = @Translation("Safe unflag"),
 *   description = "A normal link to flag, a confirmation form to unflag."
 * )
 */
class SafeUnflag extends ActionLinkTypeBase {

  /**
   * {@inheritdoc}
   */
  protected function getUrl($action, FlagInterface $flag, EntityInterface $entity) {
    switch ($action) {
      case 'flag':
        return Url::fromRoute('flag.action_link_flag', [
          'flag' => $flag->id(),
          'entity_id' => $entity->id(),
        ]);

      default:
        return Url::fromRoute('flag.confirm_unflag', [
          'flag' => $flag->id(),
          'entity_id' => $entity->id(),
        ]);
    }
  }

}
