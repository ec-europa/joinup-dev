<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\joinup_group\Entity\GroupInterface;

/**
 * Page controller for the member permissions information table.
 *
 * This table is shown in a modal dialog when pressing the "Member permissions"
 * link in the Members page.
 */
class GroupMembershipPermissionsInformationController extends ControllerBase {

  /**
   * Builds the table that shows information about member permissions.
   *
   * @param \Drupal\joinup_group\Entity\GroupInterface $rdf_entity
   *   The group for which the membership permission information is rendered.
   *
   * @return array
   *   A render array containing the table.
   */
  public function build(GroupInterface $rdf_entity): array {
    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('The dragon swooped once more lower than ever, and as he turned and dived down his belly glittered white with sparkling fires of gems in the moon.'),
    ];

    $build['close'] = [
      '#type' => 'link',
      '#title' => t('Got it'),
      '#url' => Url::fromRoute('entity.rdf_entity.member_overview', [
        'rdf_entity' => $rdf_entity->id(),
      ]),
      '#attributes' => [
        'class' => ['dialog-cancel', 'button--blue', 'button-inline', 'mdl-button', 'mdl-button--accent', 'mdl-button--accent', 'mdl-button--raised'],
      ],
    ];

    return $build;
  }

}
