<?php

namespace Drupal\joinup_subscription\Plugin\ActionLink;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\flag\FlagInterface;
use Drupal\flag\Plugin\ActionLink\FormEntryTypeBase;

/**
 * Provides a flag simple link for flagging and a confirm form for unflagging.
 *
 * @ActionLinkType(
 *   id = "safe_unflag",
 *   label = @Translation("Safe unflag"),
 *   description = "A normal link to flag, a confirmation form to unflag."
 * )
 */
class SafeUnflag extends FormEntryTypeBase {

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

  /**
   * {@inheritdoc}
   */
  public function getAsFlagLink(FlagInterface $flag, EntityInterface $entity) {
    $action = $this->getAction($flag, $entity);

    // Store the original form behaviour configuration. Since we need to render
    // the flag link as "default", we temporarily change this configuration
    // so we can render the link without any ajax behaviour.
    $origin_form_behaviour = $this->configuration['form_behavior'];
    if ($action === 'flag') {
      $this->configuration['form_behavior'] = 'default';
    }
    $render = parent::getAsFlagLink($flag, $entity);
    $this->configuration['form_behavior'] = $origin_form_behaviour;

    return $render;
  }

}
