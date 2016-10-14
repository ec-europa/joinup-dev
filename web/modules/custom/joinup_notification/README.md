# Joinup notification module

The joinup notification module is a custom module that depends only on message,
message_notify, og and state machine.

It automates the notification handling by using a settings file in the
installation folder of the module.
There are two arrays in the config settings of joinup notification, the
transition_notifications and the delete_notifications.

The transition notifications are message ids indexed by the role, transition
and workflow group as shown below.
```
$config = [
  <workflow_group_id> => [
    <transition> => [
      <role> => [
        <message_id>
      ]
    ]
  ]
]
```
The delete_notifications array is not depending on states so it is has the
same approach but uses the entity type id instead of the workflow group and
the bundle instead of the transition.
```
$config = [
  <entity_type_id> => [
    <entity_bundle> => [
      <role> => [
        <message_id>
      ]
    ]
  ]
]
```
There can be multiple message ids and the roles are either site-wide or organic
groups.

All notifications are handled by a single event handler which is iterating over
the appropriate array, sending the messages to their corresponding users (those
found with the provided role).

To add a new notification, use the following procedure:

* Export the template of the notification message to the joinup_notification/config/install directory.
* Update the joinup_notification/config/install/joinup_notification.settings.yml
file's appropriate array according to the information above, to include the new
notification transition. Use the message id from the first step.
* If it is a transition notification, update the $keys array in the
joinup_notification/EventSubscriber/WorkflowTransitionEventSubscriber::getSubscribedEvents
method.
* Provide a behat test.

If you set the information in a correct way, the notification should be sent
either in the event subscriber, or in the joinup_notification_entity_delete
hook.