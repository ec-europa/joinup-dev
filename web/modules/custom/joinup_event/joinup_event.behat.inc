<?php

/**
 * @file
 * Contains \EventSubContext.
 */

use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\joinup\Traits\NodeTrait;

/**
 * Behat step definitions for testing events.
 */
class EventSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use RdfEntityTrait;
  use NodeTrait;

  /**
   * Checks if the given event belongs to the given collection.
   *
   * If there are multiple events or collections with the same name, then
   * only the first one is checked.
   *
   * @param string $title
   *   The title of the event to check.
   * @param string $collection
   *   The name of the collection to check.
   *
   * @throws \Exception
   *   Thrown when a event with the given title does not exist.
   *
   * @Then the :collection collection has a event titled :title
   */
  public function assertOgMembership($title, $collection) {
    $collection = $this->getRdfEntityByLabel($collection, 'collection');

    $events = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['title' => $title]);
    /** @var \Drupal\node\NodeInterface $event */
    $event = reset($events);

    if (empty($event)) {
      throw new \Exception("The event titled '$title' was not found.");
    }

    /** @var \Drupal\og\Plugin\Field\FieldType\OgStandardReferenceItem $group */
    foreach ($event->get(OgGroupAudienceHelper::DEFAULT_FIELD) as $group) {
      if ($group->getValue()['target_id'] == $collection->id()) {
        // Test passes.
        return;
      }
    }

    throw new \Exception("The event '$title' is not associated with collection '{$collection->label()}'.");
  }

  /**
   * Navigates to the canonical page display of a event.
   *
   * @param string $title
   *   The name of the event.
   *
   * @When (I )go to the :title event
   * @When (I )visit the :title event
   */
  public function visitEvent($title) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->getNodeByTitle($title, 'event');
    $this->visitPath($node->url());
  }

}
