<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\joinup_group\ContentCreationOptions;
use Drupal\joinup_group\Entity\GroupInterface;

/**
 * Page controller for the member permissions information table.
 *
 * Renders a table with a simplified subset of the available permissions for the
 * different roles in a group. This is intended for end users and facilitators
 * to get an overview of the actions they can take in the group.
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
    $is_moderated = $rdf_entity->isModerated();
    $content_creators = $rdf_entity->getContentCreators();
    $only_authors_can_create = $content_creators === ContentCreationOptions::FACILITATORS_AND_AUTHORS;

    $permissions_info = [
      [
        // A custom description is provided since this is intended for a non-
        // technical audience which is not familiar with Drupal permissions.
        'description' => $this->t('View published content'),
        'permitted' => [
          'member' => TRUE,
          'author' => TRUE,
          'facilitator' => TRUE,
          'owner' => TRUE,
        ],
      ],
      [
        'description' => $this->t('Start a discussion'),
        'permitted' => [
          'member' => !$only_authors_can_create,
          'author' => TRUE,
          'facilitator' => TRUE,
          'owner' => TRUE,
        ],
      ],
      [
        'description' => $this->t('Propose content for publication, pending approval'),
        // This only applies for moderated groups. The option is hidden also if
        // only facilitators and authors can create content, since in this case
        // the normal members cannot propose any content.
        'applicable' => $is_moderated && !$only_authors_can_create,
        'permitted' => [
          'member' => TRUE,
          'author' => FALSE,
          'facilitator' => FALSE,
          'owner' => FALSE,
        ],
      ],
      [
        'description' => $this->t('Approve proposed content for publication'),
        'applicable' => $is_moderated && !$only_authors_can_create,
        'permitted' => [
          'member' => FALSE,
          'author' => FALSE,
          'facilitator' => TRUE,
          'owner' => TRUE,
        ],
      ],
      [
        'description' => $is_moderated && !$only_authors_can_create ? $this->t('Publish content without approval') : $this->t('Publish content'),
        'permitted' => [
          'member' => !$is_moderated && !$only_authors_can_create,
          'author' => TRUE,
          'facilitator' => TRUE,
          'owner' => TRUE,
        ],
      ],
      [
        'description' => $this->t('Propose changes to own published content, pending approval'),
        'applicable' => $is_moderated && !$only_authors_can_create,
        'permitted' => [
          'member' => TRUE,
          'author' => FALSE,
          'facilitator' => FALSE,
          'owner' => FALSE,
        ],
      ],
      [
        'description' => $this->t('Approve proposed changes to published content'),
        'applicable' => $is_moderated && !$only_authors_can_create,
        'permitted' => [
          'member' => FALSE,
          'author' => FALSE,
          'facilitator' => TRUE,
          'owner' => TRUE,
        ],
      ],
      [
        'description' => $is_moderated && !$only_authors_can_create ? $this->t('Update own published content without approval') : $this->t('Update own published content'),
        'permitted' => [
          'member' => !$is_moderated && !$only_authors_can_create,
          'author' => TRUE,
          'facilitator' => TRUE,
          'owner' => TRUE,
        ],
      ],
      [
        'description' => $this->t('Update any content'),
        'permitted' => [
          'member' => FALSE,
          'author' => FALSE,
          'facilitator' => TRUE,
          'owner' => TRUE,
        ],
      ],
      [
        'description' => $this->t('Request deletion of own content, pending approval'),
        'applicable' => $is_moderated && !$only_authors_can_create,
        'permitted' => [
          'member' => TRUE,
          'author' => FALSE,
          'facilitator' => FALSE,
          'owner' => FALSE,
        ],
      ],
      [
        'description' => $this->t('Approve requested deletion of content'),
        'applicable' => $is_moderated && !$only_authors_can_create,
        'permitted' => [
          'member' => FALSE,
          'author' => FALSE,
          'facilitator' => TRUE,
          'owner' => TRUE,
        ],
      ],
      [
        'description' => $is_moderated && !$only_authors_can_create ? $this->t('Delete own content without approval') : $this->t('Delete own content'),
        'permitted' => [
          'member' => !$is_moderated && !$only_authors_can_create,
          'author' => TRUE,
          'facilitator' => TRUE,
          'owner' => TRUE,
        ],
      ],
      [
        'description' => $this->t('Delete any content'),
        'permitted' => [
          'member' => FALSE,
          'author' => FALSE,
          'facilitator' => TRUE,
          'owner' => TRUE,
        ],
      ],
    ];

    $build['permissions'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Permission'),
        $this->t('Member'),
        $this->t('Author'),
        $this->t('Facilitator'),
        $this->t('Owner'),
      ],
      '#attributes' => ['class' => ['form-table__member-permissions']],
    ];

    foreach ($permissions_info as $permission_info) {
      if ($permission_info['applicable'] ?? TRUE) {
        $build['permissions'][] = [
          [
            '#plain_text' => $permission_info['description'],
            '#wrapper_attributes' => [
              'class' => ['form-table__cell__description'],
            ],
          ],
          [
            '#plain_text' => $permission_info['permitted']['member'] ? '✓' : '',
            '#wrapper_attributes' => ['class' => ['form-table__cell__data']],
          ],
          [
            '#plain_text' => $permission_info['permitted']['author'] ? '✓' : '',
            '#wrapper_attributes' => ['class' => ['form-table__cell__data']],
          ],
          [
            '#plain_text' => $permission_info['permitted']['facilitator'] ? '✓' : '',
            '#wrapper_attributes' => ['class' => ['form-table__cell__data']],
          ],
          [
            '#plain_text' => $permission_info['permitted']['owner'] ? '✓' : '',
            '#wrapper_attributes' => ['class' => ['form-table__cell__data']],
          ],
        ];
      }
    }

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
