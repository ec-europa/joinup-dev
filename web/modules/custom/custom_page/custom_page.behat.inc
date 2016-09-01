<?php

/**
 * @file
 * Contains \CustomPageSubContext.
 */

use Drupal\Core\Session\AnonymousUserSession;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\NodeTrait;
use Drupal\joinup\Traits\OgTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\og\OgGroupAudienceHelper;

/**
 * Behat step definitions for testing custom pages.
 */
class CustomPageSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use NodeTrait;
  use OgTrait;
  use RdfEntityTrait;

  /**
   * Checks if the given custom page belongs to the given collection.
   *
   * If there are multiple custom pages or collections with the same name, then
   * only the first one is checked.
   *
   * @param string $title
   *   The title of the custom page to check.
   * @param string $collection
   *   The name of the collection to check.
   *
   * @throws \Exception
   *   Thrown when a custom page with the given title doesn't exist.
   *
   * @Then the :collection collection has a custom page titled :title
   */
  public function assertOgMembership($title, $collection) {
    $collection = $this->getRdfEntityByLabel($collection, 'collection');

    $custom_pages = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['title' => $title]);
    /** @var \Drupal\node\NodeInterface $custom_page */
    $custom_page = reset($custom_pages);

    if (empty($custom_page)) {
      throw new \Exception("The custom page titled '$title' was not found.");
    }

    /** @var \Drupal\og\Plugin\Field\FieldType\OgStandardReferenceItem $group */
    foreach ($custom_page->get(OgGroupAudienceHelper::DEFAULT_FIELD) as $group) {
      if ($group->getValue()['target_id'] == $collection->id()) {
        // Test passes.
        return;
      }
    }

    throw new \Exception("The custom page '$title' is not associated with collection '{$collection->label()}'.");
  }

  /**
   * Navigates to the canonical page display of a custom page.
   *
   * @param string $title
   *   The name of the custom page.
   *
   * @When (I )go to the :title custom page
   * @When (I )visit the :title custom page
   */
  public function visitCustomPage($title) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->getNodeByTitle($title, 'custom_page');
    $this->visitPath($node->url());
  }

  /**
   * Checks the number of custom pages a collection or solution has.
   *
   * @param int $count
   *   The expected number of custom pages.
   * @param string $name
   *   The name of the collection or solution to which the custom pages belong.
   * @param string $type
   *   The RDF entity type to which the custom pages belong. Can be either
   *   'collection' or 'solution'.
   *
   * @Then the :name :type should have :number custom page(s)
   */
  public function assertContactInformationCount($count, $name, $type) {
    $group = $this->getRdfEntityByLabel($name, $type);
    $this->assertGroupContentCount($count, $group, 'node', 'custom_page');
  }

  /**
   * Checks if the user does not have access to the OG Menu administration UI.
   *
   * We only expose the OG Menu instance overview to the end user. We do not
   * expose any of the other administration pages. The adding of menu links to
   * the OG Menus is handled behind the scenes, e.g. when a custom page is
   * created a link is automatically added to the navigation menu.
   *
   * @param string $menu
   *   The name of the OG Menu instance of which the administration pages should
   *   be inaccessible.
   * @param string $title
   *   The title of the group to which the OG Menu instance belongs.
   * @param string $type
   *   The entity type of the group to which the OG Menu instance belongs.
   *
   * @throws \Exception
   *   Thrown when no OG Menu instance was found that matches the given
   *   arguments.
   *
   * @Then I should not have access to the menu link administration pages for the :menu menu of the :title :type
   */
  public function assertNoAccessToOgMenuAdministration($menu, $title, $type) {
    // Retrieve the OG Menu instance for the given menu and group.
    $group = $this->getRdfEntityByLabel($title, $type);
    $values = [
      'type' => $menu,
      OgGroupAudienceHelper::DEFAULT_FIELD => $group->id(),
    ];
    $instances = \Drupal::entityTypeManager()->getStorage('ogmenu_instance')->loadByProperties($values);
    if (empty($instances)) {
      throw new \Exception("There is no $menu menu associated with the $title $type.");
    }
    /** @var \Drupal\og_menu\Entity\OgMenuInstance $instance */
    $instance = reset($instances);
    $menu = $instance->bundle();

    // Compile a list of routes to which the user should not have access.
    // Note that this excludes the 'entity.ogmenu_instance.edit_form' route to
    // which the collection facilitator should have access; this is used to
    // reorder the pages in the menu.
    $routes = [
      'entity.ogmenu_instance.canonical' => [
        'ogmenu_instance' => $instance->id(),
      ],
      'entity.ogmenu_instance.delete_form' => [
        'ogmenu_instance' => $instance->id(),
      ],
      'entity.ogmenu_instance.add_link' => [
        'ogmenu_instance' => $instance->id(),
      ],
      'entity.ogmenu_instance.create' => [
        'ogmenu' => $menu,
        'og_group_entity_type' => $group->getEntityTypeId(),
        'og_group' => $group->id(),
      ],
      'entity.ogmenu.collection' => [],
      'entity.ogmenu.add_form' => [],
      'entity.ogmenu.overview_form' => ['ogmenu' => $menu],
      'entity.ogmenu.edit_form' => ['ogmenu' => $menu],
      'entity.ogmenu.delete_form' => ['ogmenu' => $menu],
    ];

    // Check if the currently logged in user has access to these routes.
    $current_user = $this->getUserManager()->getCurrentUser();
    $user = $current_user ? \Drupal::entityTypeManager()->getStorage('user')->loadUnchanged($current_user->uid) : new AnonymousUserSession();
    foreach ($routes as $route => $parameters) {
      $url = \Drupal\Core\Url::fromRoute($route, $parameters);
      if ($url->access($user)) {
        $uri = $url->toString();
        throw new \Exception("The current user has access to '$uri' in the OG Menu administration for the $menu menu of the $title $type.");
      }
    }
  }

}
