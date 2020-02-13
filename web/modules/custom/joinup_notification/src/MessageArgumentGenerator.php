<?php

declare(strict_types = 1);

namespace Drupal\joinup_notification;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Url;
use Drupal\og\OgMembershipInterface;
use Drupal\og\OgRoleInterface;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Static helper methods for compiling message arguments.
 */
class MessageArgumentGenerator {

  /**
   * Returns a set of arguments for the user initiating the action.
   *
   * @param \Drupal\user\UserInterface|null $actor
   *   Optional user object for the actor. Defaults to the current user.
   *
   * @return array
   *   An associative array of actor data, with the following keys:
   *   - '@actor:field_user_family_name': The last name of the actor.
   *   - '@actor:field_user_first_name': The first name of the actor.
   *   - '@actor:full_name': The full name of the actor.
   *   - '@actor:role': Will sometimes contain the word 'moderator'.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *   Thrown when the first name or last name is not known.
   */
  public static function getActorArguments(UserInterface $actor = NULL): array {
    $arguments = [];

    // Default to the current user.
    if (empty($actor)) {
      $actor = User::load(\Drupal::currentUser()->id());
    }

    $actor_first_name = !empty($actor->get('field_user_first_name')->first()->value) ? $actor->get('field_user_first_name')->first()->value : '';
    $actor_family_name = !empty($actor->get('field_user_family_name')->first()->value) ? $actor->get('field_user_family_name')->first()->value : '';

    $arguments['@actor:field_user_first_name'] = $actor_first_name;
    $arguments['@actor:field_user_family_name'] = $actor_family_name;

    if ($actor->isAnonymous()) {
      // If an anonymous is creating content, set the first name to also be 'the
      // Joinup Moderation Team' because some emails use only the first name
      // instead of the full name.
      $arguments['@actor:role'] = 'moderator';
      $arguments['@actor:full_name'] = $arguments['@actor:field_user_first_name'] = 'the Joinup Moderation Team';
    }
    elseif ($actor->hasRole('moderator')) {
      /** @var \Drupal\user\RoleInterface $role */
      $role = Role::load('moderator');
      $arguments['@actor:role'] = $role->label();
      $arguments['@actor:full_name'] = 'The Joinup Support Team';
    }
    elseif (!$actor->isAnonymous()) {
      $arguments['@actor:full_name'] = empty($actor->get('full_name')->value) ? $actor_first_name . ' ' . $actor_family_name : $actor->get('full_name')->value;
    }

    return $arguments;
  }

  /**
   * Returns an argument containing the URL to the contact form.
   *
   * @return array
   *   An associative array with a single key '@site:contact_url' and the
   *   absolute URL of the contact form as value.
   */
  public static function getContactFormUrlArgument(): array {
    return [
      '@site:contact_url' => Url::fromRoute('contact_form.contact_page', [], ['absolute' => TRUE])->toString(),
    ];
  }

  /**
   * Returns a set of arguments for the passed in group.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group for which to generate message arguments.
   *
   * @return array
   *   An associative array of message arguments, with the following keys:
   *   - '@group:title': The group title.
   *   - '@group:bundle': The group bundle, either 'solution' or 'collection'.
   *   - '@group:url': The canonical URL for the group.
   */
  public static function getGroupArguments(EntityInterface $group): array {
    $arguments = [
      '@group:title' => $group->label(),
      '@group:bundle' => $group->bundle(),
    ];

    try {
      $arguments['@group:url'] = $group->toUrl('canonical', ['absolute' => TRUE])->toString();
    }
    catch (EntityMalformedException $e) {
      // No URL could be generated.
      $arguments['@group:url'] = '';
    }

    return $arguments;
  }

  /**
   * Returns a set of arguments for the passed in membership.
   *
   * @param \Drupal\og\OgMembershipInterface $membership
   *   The membership for which to generate message arguments.
   *
   * @return array
   *   An associative array of message arguments, with the following keys:
   *   - '@membership:group:title': The group title.
   *   - '@membership:group:bundle': The group bundle, either 'solution' or
   *     'collection'.
   *   - '@membership:group:url': The canonical URL for the group.
   *   - '@membership:roles': A comma-separated list of roles.
   */
  public static function getOgMembershipArguments(OgMembershipInterface $membership): array {
    $arguments = [];

    $group = $membership->getGroup();
    foreach (static::getGroupArguments($group) as $key => $argument) {
      $key = str_replace('@', '@membership:', $key);
      $arguments[$key] = $argument;
    }

    $roles = [];
    foreach ($membership->getRoles() as $role) {
      // Skip the required role 'member', this is implied.
      if ($role->isRequired()) {
        continue;
      }

      // If the user is an administrator they will also have inherited the
      // facilitator role. Having multiple roles might be confusing for
      // non-technical users. Let's just call them the 'owner'.
      // Note that in Joinup the OG admin roles don't have the `is_admin` flag
      // set because this would unlock unwanted permissions, so we cannot use
      // `$role->isAdmin()` here. Instead we check if the role name matches.
      if ($role->getName() === OgRoleInterface::ADMINISTRATOR) {
        $roles = ['owner'];
        break;
      }

      $roles[] = $role->getName();
    }

    // If the user has no 'special' roles, then the user is a regular member.
    if (empty($roles)) {
      $roles[] = 'member';
    }

    $arguments['@membership:roles'] = implode(', ', $roles);

    return $arguments;
  }

}
