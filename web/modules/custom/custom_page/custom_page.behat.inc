<?php

/**
 * @file
 * Contains \CustomPageSubContext.
 */

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

}
