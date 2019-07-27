Joinup Subscription
===================

This module handles subscribing to content in Joinup. If a user is subscribed
they will receive email notifications when new content is being published. At
the moment there are two distinct subscription systems offered by the module:

Discussion subscriptions
------------------------

A user can subscribe to an individual discussion to be notified when new
comments are posted in the discussion. This functionality is offered to all
authenticated users through the 'Subscribe' button which is placed on the
discussion page. See the scenario `discussion.subscribe.feature` for a full
description of the functionality.

This is implemented using a Flag, and a service is available to easily perform a
subscription manually. See the following files for more info:

- [`flag.flag.subscribe_discussions.yml`](./config/install/flag.flag.subscribe_discussions.yml):
  The Flag config.
- [`JoinupSubscriptionInterface](./src/JoinupSubscriptionInterface.php): The
  service to manage discussion subscriptions. 

Collection content subscriptions
--------------------------------

A user can subscribe to content in collections and will be notified when new
content is published in the collections they are a member of. The user can
subscribe to collections through the "My subscriptions" link in the user profile
menu. This leads to the `SubscriptionDashboardForm` where the user can choose
for which content bundles they want to receive notifications.

When a user joins a collection they will be presented with a modal dialog that
allows them to subscribe immediately after joining. This modal dialog can be
found in the Collection module.

For a full description of the functionality, see the following scenarios:

- [`subscriptions_manage.feature`](../../../../tests/features/joinup_subscription/subscriptions_manage.feature)
- [`subscribe-on-join.feature`](../../../../tests/features/joinup_subscription/subscribe-on-join.feature)
- [`unsubscribe.feature`](../../../../tests/features/joinup_subscription/unsubscribe.feature)

The collection content subscriptions are stored in the `subscription_bundles`
base field on the `OgMembership` entity. This is added in
`joinup_subscription_entity_base_field_info()`.

There is no helper service at the moment to handle storing and retrieving this
data since it is handled trivially through the Field API:

```php
// Retrieving subscriptions from a membership.
$subscriptions = $membership->get('subscription_bundles');

// Storing subscriptions on a membership.
$subscription_bundles = [
  ['entity_type' => 'node', 'bundle' => 'event'],
  ['entity_type' => 'node', 'bundle' => 'news'],
];
$membership->set('subscription_bundles', $subscription_bundles)->save();
```

Digests
-------

The notifications are sent in digest format. The user can choose whether to
receive daily, weekly or monthly digests. This preference is stored in the
`field_user_frequency` field on the user entity. The digest messages are handled
by the Message Digest module. The 'monthly' interval is not available by default
in the module so is exported by us.

The user can manage the digest message frequence through the Subscription
Dashboard form which is reachable through the "My subscriptions" link in the
user profile menu.
