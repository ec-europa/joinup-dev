<?php

/**
 * @file
 * Contains \EventSubContext.
 */

use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\DrupalExtension\Hook\Scope\BeforeNodeCreateScope;
use Drupal\joinup\Traits\NodeTrait;

/**
 * Behat step definitions for testing events.
 */
class EventSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use NodeTrait;

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

  /**
   * Converts relative dates into an accepted format.
   *
   * @param \Drupal\DrupalExtension\Hook\Scope\BeforeNodeCreateScope $scope
   *   An object containing the entity properties and fields that are to be used
   *   for creating the node as properties on the object.
   *
   * @throws \Exception
   *   Thrown when an invalid format is specified for the dates.
   *
   * @BeforeNodeCreate
   */
  public static function massageEventFieldsBeforeNodeCreate(BeforeNodeCreateScope $scope) {
    $node = $scope->getEntity();

    if ($node->type !== 'event') {
      return;
    }

    foreach (['start date', 'end date'] as $field) {
      if (isset($node->$field)) {
        $date = strtotime($node->$field);

        if ($date === FALSE) {
          throw new \Exception(sprintf('Invalid format for date specified: %s', $node->$field));
        }

        $node->$field = $date;
      }
    }
  }

}
