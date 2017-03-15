<?php

namespace Drupal\Tests\joinup_discussion\Functional;

use Drupal\Tests\joinup_core\Functional\NodeWorkflowTestBase;

/**
 * Tests CRUD operations and workflow transitions for the discussion node.
 *
 * @group workflow
 */
class DiscussionWorkflowTest extends NodeWorkflowTestBase {

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
          // By default, og returns the 'member' role as part of the user roles.
          // @see: \Drupal\og\Entity\OgMembership::getRoles().
          'userOgAdministrator',
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
      self::POST_MODERATION => [
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
        'archived' => [
          'view' => [
            'userOwner',
            'userModerator',
            'userOgFacilitator',
          ],
          'update' => [],
          'delete' => [
            'userModerator',
            'userOgFacilitator',
          ],
        ],
      ],
    ];

    $parent_access_array = [];
    $return_array = [];
    // The only think affected by whether the parent is published or not, is the
    // view permission. We are using 'draft' state for an unpublished parent and
    // 'validated' state for published.
    // For the published state, everyone should be able to see published
    // content.
    foreach (['collection', 'solution'] as $parent_bundle) {
      foreach (['draft', 'validated'] as $parent_state) {
        $parent_access_array[$parent_bundle][$parent_state] = $access_array;
        foreach ($access_array as $moderation_state => $moderation_data) {
          foreach ($moderation_data as $content_state => $operation_data) {
            if ($parent_state === 'validated' && $this->isPublishedState($content_state)) {
              $parent_access_array[$parent_state][$moderation_state][$content_state]['view'] = [
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

    return $return_array;
  }

  /**
   * {@inheritdoc}
   */
  protected function isPublishedState($state) {
    $states = [
      'validated',
      'archived',
    ];

    return in_array($state, $states);
  }

  /**
   * {@inheritdoc}
   */
  protected function workflowTransitionsProvider() {
    $access_array = [
      self::POST_MODERATION => [
        '__new__' => [],
        'validated' => [
          'userAuthenticated' => [],
          'userOwner' => [
            'update_published',
          ],
          'userOgMember' => [],
          'userOgFacilitator' => [
            'update_published',
            'request_changes',
            'report',
            'disable',
          ],
          'userModerator' => [
            'update_published',
            'request_changes',
            'report',
            'disable',
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
        // Once the node is in archived state, no actions can be taken anymore.
        'archived' => [
          'userAuthenticated' => [],
          'userOwner' => [],
          'userOgMember' => [],
          'userOgFacilitator' => [],
          'userModerator' => [],
        ],
      ],
    ];

    // The allowed transitions remain between parent group's bundle regardless
    // of the moderation or publication status.
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

    // Special handle the transitions to create an entity that are affected by
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
   * Retrieves the allowed conditions per parent's eLibrary settings.
   *
   * @var int $e_library
   *    The eLibrary settings for the parent.
   * @var int $moderation
   *    The moderation settings for the parent.
   *
   * @return array
   *   An array with users as keys and allowed transitions as values.
   */
  protected function getWorkflowElibraryCreationRoles($e_library, $moderation) {
    $allowed_roles = [
      self::ELIBRARY_ONLY_FACILITATORS => [
        self::POST_MODERATION => [
          'userOgFacilitator' => [
            'validate',
          ],
          'userModerator' => [
            'validate',
          ],
        ],
      ],
      self::ELIBRARY_MEMBERS_FACILITATORS => [
        self::POST_MODERATION => [
          'userOgMember' => [
            'validate',
          ],
          'userOgFacilitator' => [
            'validate',
          ],
          'userModerator' => [
            'validate',
          ],
        ],
      ],
      self::ELIBRARY_REGISTERED_USERS => [
        self::POST_MODERATION => [
          'userAuthenticated' => [
            'validate',
          ],
          'userOgMember' => [
            'validate',
          ],
          'userOgAdministrator' => [
            'validate',
          ],
          'userOgFacilitator' => [
            'validate',
          ],
          'userModerator' => [
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
    return 'discussion';
  }

}
