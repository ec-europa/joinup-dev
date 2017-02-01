<?php

namespace Drupal\Tests\joinup_document\Functional;

use Drupal\Tests\joinup_core\NodeWorkflowTestBase;

/**
 * Tests CRUD operations and workflow transitions for the document node.
 */
class DocumentWorkflowTest extends NodeWorkflowTestBase {

  /**
   * {@inheritdoc}
   */
  protected function createAccessProvider() {
    return [
      'collection' => [
        self::ELIBRARY_ONLY_FACILITATORS => [
          'userModerator',
          'userOgFacilitator',
        ],
        self::ELIBRARY_MEMBERS_FACILITATORS => [
          'userModerator',
          'userOgMember',
          'userOgFacilitator',
          'userOgAdministrator',
        ],
        self::ELIBRARY_REGISTERED_USERS => [
          'userAuthenticated',
          'userModerator',
          'userOgMember',
          'userOgFacilitator',
          'userOgAdministrator',
        ],
      ],
      'solution' => [
        self::ELIBRARY_ONLY_FACILITATORS => [
          'userModerator',
          'userOgFacilitator',
        ],
        self::ELIBRARY_MEMBERS_FACILITATORS => [
          'userModerator',
          'userOgFacilitator',
        ],
        self::ELIBRARY_REGISTERED_USERS => [
          'userAuthenticated',
          'userModerator',
          'userOgFacilitator',
          // The following users also have access due to being authenticated.
          'userOgMember',
          'userOgAdministrator',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function readUpdateDeleteAccessProvider() {
    $access_array = [
      self::PRE_MODERATION => [
        'draft' => [
          'view' => [
            'userOwner',
            'userModerator',
            'userOgFacilitator',
          ],
          'update' => [
            'userOwner',
            'userModerator',
            'userOgFacilitator',
          ],
          'delete' => [
            'userModerator',
            'userOgFacilitator',
          ],
        ],
        'proposed' => [
          'view' => [
            'userOwner',
            'userModerator',
            'userOgFacilitator',
          ],
          'update' => [
            'userOwner',
            'userModerator',
            'userOgFacilitator',
          ],
          'delete' => [
            'userModerator',
            'userOgFacilitator',
          ],
        ],
        'validated' => [
          'view' => [
            'userOwner',
            'userModerator',
            'userOgFacilitator',
          ],
          'update' => [
            'userOwner',
            'userModerator',
            'userOgFacilitator',
          ],
          'delete' => [
            'userModerator',
            'userOgFacilitator',
          ],
        ],
        'request_deletion' => [
          'view' => [
            'userOwner',
            'userModerator',
            'userOgFacilitator',
          ],
          'update' => [
            'userModerator',
            'userOgFacilitator',
          ],
          'delete' => [
            'userModerator',
            'userOgFacilitator',
          ],
        ],
        'in_assessment' => [
          'view' => [
            'userOwner',
            'userModerator',
            'userOgFacilitator',
          ],
          'update' => [
            'userModerator',
            'userOgFacilitator',
          ],
          'delete' => [
            'userModerator',
            'userOgFacilitator',
          ],
        ],
      ],
      self::POST_MODERATION => [
        'draft' => [
          'view' => [
            'userOwner',
            'userModerator',
            'userOgFacilitator',
          ],
          'update' => [
            'userOwner',
            'userModerator',
            'userOgFacilitator',
          ],
          'delete' => [
            'userOwner',
            'userModerator',
            'userOgFacilitator',
          ],
        ],
        'proposed' => [
          'view' => [
            'userOwner',
            'userModerator',
            'userOgFacilitator',
          ],
          'update' => [
            'userOwner',
            'userModerator',
            'userOgFacilitator',
          ],
          'delete' => [
            'userOwner',
            'userModerator',
            'userOgFacilitator',
          ],
        ],
        'validated' => [
          'view' => [
            'userOwner',
            'userModerator',
            'userOgFacilitator',
          ],
          'update' => [
            'userOwner',
            'userModerator',
            'userOgFacilitator',
          ],
          'delete' => [
            'userOwner',
            'userModerator',
            'userOgFacilitator',
          ],
        ],
        'in_assessment' => [
          'view' => [
            'userOwner',
            'userModerator',
            'userOgFacilitator',
          ],
          'update' => [
            'userModerator',
            'userOgFacilitator',
          ],
          'delete' => [
            'userOwner',
            'userModerator',
            'userOgFacilitator',
          ],
        ],
      ],
    ];

    $parent_access_array = [];
    $return_array = [];
    // The only think affected by whether the parent is published is the view
    // permission. We are using 'draft' state for an unpublished parent and
    // 'validated' state for published.
    // For the published state, everyone should be able to see published
    // content.
    foreach (['collection', 'solution'] as $parent_bundle) {
      foreach (['draft', 'validated'] as $parent_state) {
        $parent_access_array[$parent_bundle][$parent_state] = $access_array;
        foreach ($access_array as $moderation_state => $moderation_data) {
          foreach ($moderation_data as $content_state => $operation_data) {
            foreach ($operation_data as $operation => $roles) {
              if ($parent_state === 'validated' && $this->isPublishedState($content_state)) {
                $parent_access_array[$parent_state][$moderation_state][$content_state][$operation] = [
                  'userOwner',
                  'userAuthenticated',
                  'userModerator',
                  'userOgMember',
                  'userOgFacilitator',
                  'userOgAdministrator',
                ];
              }
            }
          }
        }
      }
    }

    return $return_array;
  }

  /**
   * {@inheritdoc}
   */
  protected function workflowTransitionsProvider() {
    $access_array = [
      self::PRE_MODERATION => [
        '__new__' => [],
        'draft' => [
          'userAuthenticated' => [],
          'userOwner' => [
            'propose',
          ],
          'userOgMember' => [],
          'userOgFacilitator' => [
            'validate',
          ],
          'userModerator' => [
            'validate',
          ],
        ],
        'proposed' => [
          'userAuthenticated' => [],
          'userOwner' => [
            'update_proposed',
          ],
          'userOgMember' => [],
          'userOgFacilitator' => [
            'update_proposed',
            'approve_proposed',
          ],
          'userModerator' => [
            'update_proposed',
            'approve_proposed',
          ],
        ],
        'validated' => [
          'userAuthenticated' => [],
          'userOwner' => [
            'request_deletion',
          ],
          'userOgMember' => [],
          'userOgFacilitator' => [
            'report',
            'update_published',
            'request_changes',
          ],
          'userModerator' => [
            'report',
            'update_published',
            'request_changes',
          ],
        ],
        'in_assessment' => [
          'userAuthenticated' => [],
          'userOwner' => [],
          'userOgMember' => [],
          'userOgFacilitator' => [
            'approve_report',
          ],
          'userModerator' => [
            'approve_report',
          ],
        ],
        'deletion_request' => [
          'userAuthenticated' => [],
          'userOwner' => [],
          'userOgMember' => [],
          'userOgFacilitator' => [
            'reject_deletion',
          ],
          'userModerator' => [
            'reject_deletion',
          ],
        ],
      ],
      self::POST_MODERATION => [
        '__new__' => [],
        'draft' => [
          'userAuthenticated' => [],
          'userOwner' => [
            'validate',
          ],
          'userOgMember' => [],
          'userOgFacilitator' => [
            'validate',
          ],
          'userModerator' => [
            'validate',
          ],
        ],
        'proposed' => [
          'userAuthenticated' => [],
          'userOwner' => [
            'update_proposed',
          ],
          'userOgMember' => [],
          'userOgFacilitator' => [
            'update_proposed',
            'approve_proposed',
          ],
          'userModerator' => [
            'update_proposed',
            'approve_proposed',
          ],
        ],
        'validated' => [
          'userAuthenticated' => [],
          'userOwner' => [
            'update_published',
          ],
          'userOgMember' => [],
          'userOgFacilitator' => [
            'report',
            'update_published',
            'request_changes',
          ],
          'userModerator' => [
            'report',
            'update_published',
            'request_changes',
          ],
        ],
        'in_assessment' => [
          'userAuthenticated' => [],
          'userOwner' => [],
          'userOgMember' => [],
          'userOgFacilitator' => [
            'approve_report',
          ],
          'userModerator' => [
            'approve_report',
          ],
        ],
      ],
    ];

    $parent_access_array = [];
    $return_array = [];
    $e_library_states = $this->getElibraryStates();

    foreach ($access_array as $moderation_state => $moderation_data) {
      foreach ($moderation_data as $content_state => $user_var_data) {
        foreach (['collection', 'solution'] as $parent_bundle) {
          foreach (['draft', 'validated'] as $parent_state) {
            $parent_access_array[$parent_bundle][$parent_state] = $access_array;
            foreach ($e_library_states as $e_library) {
              $return_array[$parent_bundle][$e_library] = $access_array;
            }
          }
        }
      }
    }

    // Special handle the create conditions that are affected by
    // eLibrary and moderation.
    foreach ($return_array as $parent_bundle => $parent_data) {
      foreach ($parent_data as $e_library => $e_library_data) {
        foreach ($e_library_data as $moderation_state => $moderation_data) {
          $return_array[$parent_bundle][$e_library][$moderation_state]['__new__'] = $this->getWorkflowElibraryCreationRoles($e_library, $moderation_state);
        }
      }
    }

    return $return_array;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublishedState($state) {
    $states = [
      'validated',
      'in_assessment',
      'request_deletion',
    ];

    return in_array($state, $states);
  }

  /**
   * Retrieves the allowed conditions per parent's eLibrary settings.
   *
   * @var int $e_library
   *    The eLibrary settings for the parent.
   * @var int $moderation
   *    The moderation settings for the parent.
   *
   * @return array
   *    An array with users as keys and allowed transitions as values.
   */
  protected function getWorkflowElibraryCreationRoles($e_library, $moderation) {
    $allowed_roles = [
      self::ELIBRARY_ONLY_FACILITATORS => [
        self::PRE_MODERATION => [
          'userOgFacilitator' => [
            'propose',
            'save_as_draft',
            'validate',
          ],
          'userModerator' => [
            'propose',
            'save_as_draft',
            'validate',
          ],
        ],
        self::POST_MODERATION => [
          'userOgFacilitator' => [
            'save_as_draft',
            'validate',
          ],
          'userModerator' => [
            'save_as_draft',
            'validate',
          ],
        ],
      ],
      self::ELIBRARY_MEMBERS_FACILITATORS => [
        self::PRE_MODERATION => [
          'userOgMember' => [
            'save_as_draft',
            'propose',
          ],
          'userOgFacilitator' => [
            'propose',
            'save_as_draft',
            'validate',
          ],
          'userModerator' => [
            'propose',
            'save_as_draft',
            'validate',
          ],
        ],
        self::POST_MODERATION => [
          'userOgMember' => [
            'save_as_draft',
            'validate',
          ],
          'userOgFacilitator' => [
            'save_as_draft',
            'validate',
          ],
          'userModerator' => [
            'save_as_draft',
            'validate',
          ],
        ],
      ],
      self::ELIBRARY_REGISTERED_USERS => [
        self::PRE_MODERATION => [
          'userAuthenticated' => [
            'save_as_draft',
            'propose',
          ],
          'userOgMember' => [
            'save_as_draft',
            'propose',
          ],
          'userOgAdministrator' => [
            'save_as_draft',
            'propose',
          ],
          'userOgFacilitator' => [
            'save_as_draft',
          // For being a member as well.
            'propose',
            'validate',
          ],
          'userModerator' => [
            'save_as_draft',
          // For being an authenticated user.
            'propose',
            'validate',
          ],
        ],
        self::POST_MODERATION => [
          'userAuthenticated' => [
            'save_as_draft',
            'validate',
          ],
          'userOgMember' => [
            'save_as_draft',
            'validate',
          ],
          'userOgAdministrator' => [
            'save_as_draft',
            'validate',
          ],
          'userOgFacilitator' => [
            'save_as_draft',
            'validate',
          ],
          'userModerator' => [
            'save_as_draft',
            'validate',
          ],
        ],
      ],
    ];

    return $allowed_roles[$e_library][$moderation];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityBundle() {
    return 'document';
  }

}
