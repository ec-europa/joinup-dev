# Workflows
For the moderation of entities, the module
[state_machine](https://drupal.org/project/state_machine) was selected as the
native moderation system is working only with nodes.
Information on the state_machine can also be found in the
[github repo](https://github.com/bojanz/state_machine/blob/8.x-1.x/README.md).

##How to define the workflow:
State machine module already provides the basic functionality there.
To create a workflow the following steps are needed:
* create the two yaml files defined in the state machine (workflow_groups and
workflows) for the entity type;
* create the guard class that will handle the management of the allowed
transitions;
* create a state field in the entity type you want to be moderated.
There is already a base storage in the joinup_core module provided for a field
named field_state (for node entities);
* if more than one workflow are defined in the entity type, a workflow callback
will need to be defined for the field and the property 'workflow_callback' has
to be set.
* a settings file has to be provided for each entity type in order to define the
roles that are allowed per transition.

## Content workflows
The content workflows have a centralized handler and need to be defined in a
specific way. The content entities, in order to take advantage of the generic
handler, need to have their field's machine name as `field_state` (storage
provided by `joinup_core` module).

### The workflow
The workflow(s) defined for each entity type must have an id that follow the
pattern ```<entity_type>:<bundle>:<pre_moderated|post_moderated>```.
For example, for the node bundle `news`, for a pre-moderated workflow, the id of
the workflow will be `node:news:pre_moderated`. Most community content have a
`pre_moderated` and a `post_moderated` workflow.
If there is only one workflow needed for the file, still, one of the above
definitions need to be used. There is no need to define both though.

If more than one files are created, then a workflow selector needs to be
declared and set to the field. For joinup_news, the `field_state` instance has
the following:
```
settings:
  workflow: ''
  workflow_callback: joinup_news_workflow_selector
```
The `workflow_callback` is then called to determine which workflow will be used.
The callback receives the entity as a parameter.

Here is an example of this callback:
```
function joinup_news_workflow_selector(EntityInterface $entity) {
  if ($entity->bundle() != 'news') {
    throw new Exception('This method can only be called for document entities');
  }
  /** @var \Drupal\joinup_core\JoinupRelationManager $relation_manager */
  $relation_manager = \Drupal::service('joinup_core.relations_manager');
  $moderation = $relation_manager->getParentModeration($entity);
  $moderation_type = $moderation == 1 ? 'pre_moderated' : 'post_moderated';
  return "node:news:$moderation_type";
}
```

**Important**
For the community content, an extra state need to be created called `__new__`.
This state will be used to represent that an entity is being created. The
transitions should also take care of that.
For example, in order to create a news entity, a transition has to be made from
the state `__new__` towards an allowed state defined in the transitions.
This provides the opportunity to handle the `create` operation in a generic way
as well.

### The settings files
For the settings of the transitions, two files are needed:
* The schema file. The structure of the file is shared between the
community content but since the settings array is saved in the config entry of
the module, it needs to be defined in its schema. The structure of the file is:
```
# The array is a four dimensional array:
# 1. The first level contains the workflow id which is one per moderation
# status.
# 2. The second level is transition id.
# 3. Each transition is an array of allowed source states which in every check
#    is the current state of the entity.
# 4. Finally, the source states are arrays of roles that are allowed to perform
#    this action.
<module_name>.settings:
  type: config_object
  label: 'Joinup workflow permission settings'
  mapping:
    transitions:
      type: sequence
      label: 'Workflow id'
      sequence:
        type: sequence
        label: 'Transition id'
        sequence:
          type: sequence
          label: 'Source state'
          sequence:
            type: sequence
            label: 'Roles allowed to perform transition'
            sequence:
              type: string
              label: 'Role allowed to perform transition'

```
* The settings file. Following the schema above, the settings regarding the
workflow need to be defined in the settings file of the module. Note that the
structure should be defined as the schema commands otherwise the tests will
break for not ensuring the schema consistency.
You can find an example of the settings file in [the news moderation
settings](../joinup_news/config/install/joinup_news.settings) file.
*Notes:*
    * The last level of the 4-dimensional array is a list of roles allowed to
    perform the transition. The available roles for this array according to
    joinup implementation are:
        * authenticated
        * moderator
        * rdf_entity-collection-member
        * rdf_entity-collection-facilitator
        * rdf_entity-collection-administrator
        * rdf_entity-solution-member
        * rdf_entity-solution-facilitator
        * rdf_entity-solution-administrator
        * owner
    * The `rdf_entity-solution-member` is not used by joinup, but functionally,
    it has to be set whenever he can do something as being an authenticated user
    for example, because tests take care of edge cases as well.
    * The `owner` is a special handle and is *not* a role in the system. The
    owner represents the author of the entity, if one exists, and is not to be
    confused with the owner entity type of the rdf entities.

### The guard class
The `Guard class` will take care of the allowed transitions. This class
is now centralized and the base class exists in joinup_core. This class is
`Drupal\joinup_core\Guard\NodeGuard`. The guard classes of the community content
should extend this class and make use of the `allowed()` method -if possible-
without overriding it.
The only thing needed to be done in the guard class, is to load the
corresponding settings file from the database and store the allowed transitions
into the class. For example, in the joinup_news module, this is the constructor:

```
public function __construct(
  EntityTypeManagerInterface $entityTypeManager,
  JoinupRelationManager $relationManager,
  MembershipManagerInterface $ogMembershipManager,
  ConfigFactoryInterface $configFactory,
  AccountInterface $currentUser
  ) {
    parent::__construct(
      $entityTypeManager,
      $relationManager,
      $ogMembershipManager,
      $configFactory,
      $currentUser
    );
    $this->transitions = $this->configFactory
      ->get('joinup_news.settings')->get('transitions');
}
```
The `$this->transitions` is the array defined in the `NodeGuard` class and is
used in the `allowed()` method so it needs to be populated otherwise the
functionality will break.

By default the checks taking place in the `NodeGuard` class are (by sequence):
* if the transition array is not populated (as described above), no transitions
are allowed;
* if the passed user has the admin permission on the entity type, all
available transitions are allowed for that user;
* if the owner is allowed to perform the transition and the user is the owner,
the transition is allowed;
* if there are normal roles (authenticated, moderator) that are allowed to
perform the transition and the user is one of them, the transition is allowed;
* if there are Og roles that are allowed to perform the transition and the user
is one of them, the transition is allowed;

Note that in the above cases, the parent's eLibrary settings, in the case of a
new entity created, are already taken into account. The allowed roles are a
combination of the roles allowed by the workflow, the parent's moderation and
the parent's eLibrary settings.

### The access handler

The community content share a base access handler. The idea behind this is that
joinup is a highly complex system when it comes to moderation. Access to
transitions have a multi layered access system including the og membership
permissions, the global permissions, the group's settings regarding eLibrary and
moderation and workflow specific needs.

The idea here is that there has to be a base for all the access control and this
base will integrate with the rest.

The way this is achieved is by creating a handler that will invoke all other
handlers and finally approve or reject the operation access.

The handler is `Drupal\joinup_core\NodeWorkflowAccessControlHandler` and is also
hosted in joinup_core. joinup_core also handles the invocation of this class
using the `hook_node_access`.

The function is filtered for the bundles that have an active workflow so adding
a new bundle will have to extend the array there
(@see: joinup_core_node_access).

Per operation, this is how access is defined:
#### **View**
* Check the `view` access that the user has towards the parent group.
If the user does not have `view` access to the parent, allow access only if he
is the author of the item.
* If the user has view access to the parent, then check membership permission to
view all content (global group permissions).
* If the user still has no access to the content, return neutral and allow the
normal handler to check if he has normal permission to view the content.

#### **Create**
The `create` operation, unlike the normal access control system, requires an
entity instead of a bundle only. This is because the access control handler
requires the settings from the parent group. In joinup, we are handling access
like that in every custom access needed.
Note that access to normal `node/add/<bundle_type>` is prohibited in the
project. Users are allowed to create community content only within the context
of a group.

The access is given to the user if there are allowed transitions from the
default one (`__new__`).

**Important:**
A non saved entity must be passed as an argument as the eLibrary creation is
only taken into account for entities that have the `->isNew()` handle returning
`TRUE`.
Note that the workflow guard class will take into account the Og membership
and the parent's settings.

#### **Update**
The `update` permission, like the `create` is based on the allowed transitions
only. This will also take into account Og membership and parent group's
settings.

#### **Delete**
The `delete` operation for community content is allowed to the author only if
the parent group is post-moderated. Otherwise, it is only allowed to
facilitators and moderators and the author needs to request deletion in order to
remove it from the site.

The access check sequence is:
* if the user has the `delete any <bundle> <content>` as a site wide permission,
allow access;
* if the user has the `delete any <bundle> <content>` as an Og permission, allow
access;
* if the parent is a pre-moderated group, disallow `delete` access if the user
does not have the `delete any <bundle> <content>`;
* otherwise, the handler will return a neutral result and the default node
access handler will check if the user has the right to delete his own content.

=========================

# Testing

The community content also share a base class for testing.
The base class is `Drupal\Tests\joinup_core\Functional\NodeWorkflowTestBase` and
all the logic of the testing resides there.
For the purposes of the testing, the following users are being used:
* **$userOwner**: the user will be assigned as an author of every content
  created.
* **$userAuthenticated**: the user has only the `authenticated` role assigned
  and is not assigned with an Og role to the entities.
* **$userModerator**: the user is assigned the `moderator` role and is also not
  assigned with an Og role to the entities.
* **$userOgMember**: the user with the `authenticated` role that is
  automatically assigned as a member to any group entity created. Note that this
  user is assigned as a member to solutions as well because even if we do not
  use the notion of a member, we still need to ensure that there are no security
  leaks.
* **$userOgFacilitator**: the user with the `authenticated` role that is
  automatically assigned as a facilitator to any group entity created.
* **$userOgAdministrator**: the user with the `authenticated` role that is
  automatically assigned as an administrator to any group entity created.

**Note**
The `\Drupal\og\OgMembership::getRoles()`, the
`Drupal\Core\Session\UserSession::getRoles()` and the
`Drupal\Core\Session\AccountProxy::getRoles()` always return the locked roles by
default. The locked roles for Og are the Og anonymous user and the Og member.
The site wide locked roles are `authenticated` and `anonymous`.
The access control handler offered by joinup_core takes this into account so the
tests should also treat users the same. That means that all users have the
ability to perform actions that authenticated users can and then all users who
belong to a group have the ability to perform actions that members can.

The class also includes 4 abstract methods that have to be implemented in order
for the workflow test to work properly. These 4 methods are:
* **createAccessProvider**
An array with the cases to check when it comes to creating an entity.
The structure of the array is:
```
$access_array = [
  'parent_bundle' => [
    'elibrary_status' => [
      'user variable 1',
      'user variable 2',
     ],
   ],
 ];
```
**Note**
No parent state needs to be checked as it does not affect the possibility
to create document. Also, no moderation setting is needed as it also does not
affect the outcome.
The last level of the array is meant for one of the available users provided by
the base class as defined earlier.

* **readUpdateDeleteAccessProvider**
The entity operation access check. The structure of the array is
```
 $access_array = [
  'parent_bundle' => [
    'parent_state' => [
      'parent_moderation' => [
        'entity_state' => [
          'operation' => [
            'user variable 1',
            'user variable 2',
           ],
         ],
       ],
     ],
   ],
 ];
```
The last level of the array is meant for one of the available users provided by
the base class as defined earlier.
The test is also checking negative cases, so for the user variables not present
in each array, there will be a check to ensure that the user does not have the
corresponding operation access.

* **workflowTransitionsProvider**
Tests the transitions available for each case. The structure of the array is:
```
$workflow_array = [
  'parent_bundle' => [
    'parent_e_library' => [
      'parent_moderation' => [
        'entity_state' => [
          'user variable' => [
            'transition',
            'transition',
          ],
        ],
      ],
    ],
  ],
];
```
This test does not check for negative cases so if you want to ensure a user does
not have any allowed transitions, just provide him in the `user variable` level
with an empty array of transitions.

* **isPublishedState**
Since the content publication state is handled by an even provider, there is no
direct way of setting whether a state is published or not (the published flag
is set on the transition, not the state).
In order to avoid creating the content and forcing a transition, which would
complicate things a lot, the content is directly created in the state that it is
designed to be created and the list of the states where the entity is published
is provided by this method.
No logic is needed in this method as all the logic is in the base class.
