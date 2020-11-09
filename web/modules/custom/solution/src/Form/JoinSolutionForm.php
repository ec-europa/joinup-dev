<?php

declare(strict_types = 1);

namespace Drupal\solution\Form;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\joinup_community_content\CommunityContentHelper;
use Drupal\joinup_group\Form\JoinGroupFormBase;
use Drupal\og\OgMembershipInterface;

/**
 * A simple form with a button to join or leave a solution.
 */
class JoinSolutionForm extends JoinGroupFormBase {

  /**
   * Returns the label for the submit button.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The title of the label.
   */
  public function getJoinSubmitLabel(): TranslatableMarkup {
    return $this->t('Subscribe to this :type', [
      ':type' => $this->group->bundle(),
    ]);
  }

  /**
   * Returns the label for the leave submit button.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The title of the label.
   */
  public function getLeaveSubmitLabel(): TranslatableMarkup {
    $membership = $this->membershipManager->getMembership($this->group, $this->user->id(), OgMembershipInterface::ALL_STATES);
    if ($membership->hasRole($this->group->getEntityTypeId() . '-' . $this->group->bundle() . '-facilitator') || $membership->hasRole($this->group->getEntityTypeId() . '-' . $this->group->bundle() . '-author')) {
      return $this->t('Leave this :type', [
        ':type' => $this->group->bundle(),
      ]);
    }

    return $this->t('Unsubscribe from this :type', [
      ':type' => $this->group->bundle(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getSuccessMessage(OgMembershipInterface $membership): TranslatableMarkup {
    return $this->t('You have subscribed to this solution and will receive notifications for it. You can manage your subscriptions at <a href=":url">My subscriptions</a>.', [
      ':url' => Url::fromRoute('joinup_subscription.my_subscriptions', [
        'subscription_type' => 'solution',
      ])->toString(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function createMembership(string $state, array $roles): OgMembershipInterface {
    $membership = parent::createMembership($state, $roles);
    $membership->set('subscription_bundles', array_map(function (string $bundle): array {
      return ['entity_type' => 'node', 'bundle' => $bundle];
    }, CommunityContentHelper::BUNDLES));

    return $membership;
  }

}
