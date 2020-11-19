<?php

/**
 * @file
 * Post update functions for the Joinup core module.
 */

declare(strict_types = 1);

use Drupal\og\Entity\OgRole;
use Drupal\state_machine_permissions\StateMachinePermissionStringConstructor;
use Drupal\user\Entity\Role;

/**
 * Updates the permissions of the groups.
 */
function joinup_core_post_update_update_group_user_permissions() {
  $keywords = ['collection', 'solution'];
  foreach ($keywords as $group_keyword) {
    $collection_settings = \Drupal::config("{$group_keyword}.settings");
    /** @var \Drupal\state_machine\Plugin\Workflow\Workflow $collection_workflow */
    $collection_workflow = \Drupal::service('plugin.manager.workflow')->createInstance("{$group_keyword}_workflow");
    /** @var \Drupal\user\Entity\Role[] $local_roles */
    $local_roles = [
      'authenticated' => Role::load('authenticated'),
      'moderator' => Role::load('moderator'),
    ];
    /** @var \Drupal\og\Entity\OgRole[] $group_roles */
    $group_roles = [
      "rdf_entity-{$group_keyword}-facilitator" => OgRole::load("rdf_entity-{$group_keyword}-facilitator"),
      "rdf_entity-{$group_keyword}-administrator" => OgRole::load("rdf_entity-{$group_keyword}-administrator"),
    ];

    foreach ($collection_settings->get('transitions') as $to_state_id => $to_state_roles) {
      foreach ($to_state_roles as $from_state_id => $roles) {
        $permission_string = StateMachinePermissionStringConstructor::constructGroupStateUpdatePermission($collection_workflow, $from_state_id, $to_state_id);
        foreach ($roles as $role) {
          if (isset($local_roles[$role])) {
            $local_roles[$role]->grantPermission($permission_string);
          }
          if (isset($group_roles[$role])) {
            $group_roles[$role]->grantPermission($permission_string);
          }
        }
      }
    }

    foreach ($local_roles as $role) {
      $role->save();
    }
    foreach ($group_roles as $role) {
      $role->save();
    }
  }
}

/**
 * Assign update permissions for community content.
 */
function joinup_core_post_update_update_community_content_state_permissions() {
  $roles = [
    'roles' => [
      'anonymous' => Role::load('anonymous'),
      'authenticated' => Role::load('authenticated'),
      'moderator' => Role::load('moderator'),
    ],
    'og_roles' => [
      'rdf_entity-collection-member' => OgRole::load('rdf_entity-collection-member'),
      'rdf_entity-collection-author' => OgRole::load('rdf_entity-collection-author'),
      'rdf_entity-collection-facilitator' => OgRole::load('rdf_entity-collection-facilitator'),
      'rdf_entity-collection-administrator' => OgRole::load('rdf_entity-collection-administrator'),
      'rdf_entity-solution-member' => OgRole::load('rdf_entity-solution-member'),
      'rdf_entity-solution-author' => OgRole::load('rdf_entity-solution-author'),
      'rdf_entity-solution-facilitator' => OgRole::load('rdf_entity-solution-facilitator'),
      'rdf_entity-solution-administrator' => OgRole::load('rdf_entity-solution-administrator'),
    ],
  ];
  $view_permission_scheme = \Drupal::config("joinup_community_content.permission_scheme")->get('update');
  /** @var \Drupal\state_machine\WorkflowManagerInterface $workflow_manager */
  $workflow_manager = \Drupal::service('plugin.manager.workflow');

  foreach (['document', 'discussion', 'event', 'news'] as $bundle) {
    foreach ($view_permission_scheme as $workflow_id => $to_states) {
      // Skip the rest of the workflows for discussions and the discussion
      // workflow for the rest of the content.
      if (($bundle === 'discussion' && $workflow_id !== 'node:discussion:post_moderated') || ($bundle !== 'discussion' && $workflow_id === 'node:discussion:post_moderated')) {
        continue;
      }
      /** @var \Drupal\state_machine\Plugin\Workflow\Workflow $workflow */
      $workflow = $workflow_manager->createInstance($workflow_id);
      foreach ($to_states as $to_state => $from_states) {
        foreach ($from_states as $from_state => $own_any_scheme) {
          foreach ($own_any_scheme as $own_any_key => $role_scheme) {
            $permission_string = StateMachinePermissionStringConstructor::constructTransitionPermission('node', $bundle, $workflow, $from_state, $to_state, $own_any_key === 'any');
            $role_scheme += ['roles' => [], 'og_roles' => []];
            foreach (['roles', 'og_roles'] as $role_type) {
              foreach ($role_scheme[$role_type] as $role) {
                $roles[$role_type][$role]->grantPermission($permission_string);
              }
            }
          }
        }
      }
    }
  }

  foreach (['roles', 'og_roles'] as $role_type) {
    foreach ($roles[$role_type] as $role) {
      $role->save();
    }
  }
}
