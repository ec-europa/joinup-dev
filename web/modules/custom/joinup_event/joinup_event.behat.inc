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
   * Checks if the given event belongs to the given parent rdf entity.
   *
   * If there are multiple events or parents with the same name, then
   * only the first one is checked.
   *
   * @param string $event
   *   The title of the event to check.
   * @param string $bundle
   *   The bundle of the parent.
   * @param string $parent
   *   The name of the collection to check.
   *
   * @throws \Exception
   *   Thrown when a event with the given title does not exist.
   *
   * @Then the :collection :bundle has a event titled :title
   */
  public function assertOgMembership($event, $bundle, $parent) {
    $parent = $this->getRdfEntityByLabel($parent, $bundle);

    $events = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['title' => $event]);
    /** @var \Drupal\node\NodeInterface $event */
    $event = reset($events);

    if (empty($event)) {
      throw new \Exception("The event titled '$event' was not found.");
    }

    /** @var \Drupal\og\Plugin\Field\FieldType\OgStandardReferenceItem $group */
    foreach ($event->get(OgGroupAudienceHelper::DEFAULT_FIELD) as $group) {
      if ($group->getValue()['target_id'] == $parent->id()) {
        // Test passes.
        return;
      }
    }

    throw new \Exception("The event '$event' is not associated with the '{$parent->label()}' {$bundle}.");
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
