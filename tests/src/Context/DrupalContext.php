<?php

namespace Drupal\joinup\Context;

use Drupal\DrupalExtension\Context\DrupalContext as DrupalExtensionDrupalContext;
use Drupal\og\Og;

/**
 * Provides step definitions for interacting with Drupal.
 */
class DrupalContext extends DrupalExtensionDrupalContext {

  /**
   * {@inheritdoc}
   */
  public function loggedIn() {
    $session = $this->getSession();
    $session->visit($this->locatePath('/'));

    // Check if the 'logged-in' class is present on the page.
    $element = $session->getPage();
    return $element->find('css', 'body.user-logged-in');
  }

  /**
   * Checks if a node of a certain type with a given title exists.
   *
   * @param string $type
   *   The node type.
   * @param string $title
   *   The title of the node.
   *
   * @Then I should have a :type (content )page titled :title
   */
  public function assertContentPageByTitle($type, $title) {
    $type = $this->getEntityByLabel('node_type', $type);
    // If the node doesn't exist, the exception will be thrown here.
    $this->getEntityByLabel('node', $title, $type->id());
  }

  /**
   * Returns the entity with the given type, bundle and label.
   *
   * If multiple entities have the same label then the first one is returned.
   *
   * @param string $entity_type
   *   The entity type to check.
   * @param string $label
   *   The label to check.
   * @param string $bundle
   *   Optional bundle to check. If omitted, the entity can be of any bundle.
   *
   * @return \Drupal\Core\Entity\Entity
   *   The requested entity.
   *
   * @throws \Exception
   *   Thrown when an entity with the given type, label and bundle does not
   *   exist.
   */
  public function getEntityByLabel($entity_type, $label, $bundle = NULL) {
    $entity_manager = \Drupal::entityTypeManager();
    $storage = $entity_manager->getStorage($entity_type);
    $entity = $entity_manager->getDefinition($entity_type);

    $query = $storage->getQuery()
      ->condition($entity->getKey('label'), $label)
      ->range(0, 1);

    // Optionally filter by bundle.
    if ($bundle) {
      $query->condition($entity->getKey('bundle'), $bundle);
    }

    $result = $query->execute();

    if ($result) {
      $result = reset($result);
      return $storage->load($result);
    }

    throw new \Exception("The entity with label '$label' was not found.");
  }

  /**
   * Assert that certain fields are present on the page.
   *
   * @param string $fields
   *    Fields.
   *
   * @throws \Exception
   *   Thrown when a column name is incorrect.
   *
   * @Then /^(?:|the following )fields should be present? "(?P<fields>[^"]*)"$/
   */
  public function assertFieldsPresent($fields) {
    $fields = explode(',', $fields);
    $fields = array_map('trim', $fields);
    $fields = array_filter($fields);
    $not_found = [];
    foreach ($fields as $field) {
      $is_found = $this->getSession()->getPage()->find('named', array('field', $field));
      if (!$is_found) {
        $not_found[] = $field;
      }
    }
    if ($not_found) {
      throw new \Exception("Field(s) expected, but not found: " . implode(', ', $not_found));
    }
  }

  /**
   * Assert that certain fields are not present on the page.
   *
   * @param string $fields
   *    Fields.
   *
   * @throws \Exception
   *   Thrown when a column name is incorrect.
   *
   * @Then /^(?:|the following )fields should not be present? "(?P<fields>[^"]*)"$/
   */
  public function assertFieldsNotPresent($fields) {
    $fields = explode(',', $fields);
    $fields = array_map('trim', $fields);
    $fields = array_filter($fields);
    foreach ($fields as $field) {
      $is_found = $this->getSession()->getPage()->find('named', array('field', $field));
      if ($is_found) {
        throw new \Exception("Field should not be found, but is present: " . $field);
      }
    }
  }

  /**
   * Checks the users existence.
   *
   * @param string $username
   *   The username of the user.
   *
   * @throws \Exception
   *   Thrown when the user is not found.
   *
   * @Then I should have a :username user
   */
  public function assertUserExistence($username) {
    $user = user_load_by_name($username);

    if (empty($user)) {
      throw new \Exception("Unable to load expected user " . $username);
    }
  }

  /**
   * Checks if the given entity is member of the given group.
   *
   * @param string $member_title
   *   The member entity's label.
   * @param string $member_type
   *   The member entity's bundle.
   * @param string $group_id
   *   The group entity's label.
   *
   * @throws \Exception
   *   Thrown when an entity is not found or when an entity set as group is not
   *   a group.
   *
   * @Then the :member_label of type :member_type should be a member of the group with ID :group_id
   */
  public function assertOgMembership($member_title, $member_type, $group_id) {
    $member_storage = \Drupal::entityTypeManager()->getStorage($member_type);
    $members_array = $member_storage->loadByProperties(['title' => $member_title]);
    /** @var \Drupal\core\Entity\EntityInterface $member */
    $member = reset($members_array);

    if (empty($member)) {
      throw new \Exception("The entity titled $member_title was not found.");
    }
    if (!(Og::isGroupContent($member->getEntityTypeId(), $member->bundle()))) {
      throw new \Exception("The entity " . $member->getEntityTypeId() . " is not a group content.");
    }

    if ($member->og_group_ref->getValue()[0]['target_id'] != $group_id) {
      throw new \Exception("Entity \"" . $member->label() . "\" is not a member of entity with id \"" . $group_id . "\"");
    }
  }

}
