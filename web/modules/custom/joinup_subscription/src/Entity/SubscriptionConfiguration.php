<?php

namespace Drupal\joinup_subscription\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\joinup_subscription\SubscriptionConfigurationInterface;

/**
 * Defines the Subscription configuration entity.
 *
 * @ConfigEntityType(
 *   id = "subscription_configuration",
 *   label = @Translation("Subscription configuration"),
 *   handlers = {
 *     "list_builder" = "Drupal\joinup_subscription\SubscriptionConfigurationListBuilder",
 *     "form" = {
 *       "add" = "Drupal\joinup_subscription\Form\SubscriptionConfigurationForm",
 *       "edit" = "Drupal\joinup_subscription\Form\SubscriptionConfigurationForm",
 *       "delete" = "Drupal\joinup_subscription\Form\SubscriptionConfigurationDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\joinup_subscription\SubscriptionConfigurationHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "subscription_configuration",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/subscription_configuration/{subscription_configuration}",
 *     "add-form" = "/admin/structure/subscription_configuration/add",
 *     "edit-form" = "/admin/structure/subscription_configuration/{subscription_configuration}/edit",
 *     "delete-form" = "/admin/structure/subscription_configuration/{subscription_configuration}/delete",
 *     "collection" = "/admin/structure/subscription_configuration"
 *   }
 * )
 */
class SubscriptionConfiguration extends ConfigEntityBase implements SubscriptionConfigurationInterface {
  /**
   * The Subscription configuration ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Subscription configuration label.
   *
   * @var string
   */
  protected $label;

  /**
   * The uid this configuration entity refers to.
   *
   * @var string
   */
  protected $uid;

  /**
   * A list of the labels of group types indexed by their machine name.
   *
   * @var string
   */
  protected $group_types;

  /**
   * A number in seconds representing the frequency of the notifications.
   *
   * @var string
   */
  protected $frequency;

}
