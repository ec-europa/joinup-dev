<?php

/**
 * @file
 * Contains \EventSubContext.
 */

use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
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

}
