<?php

/**
 * @file
 * Contains \DiscussionSubContext.
 */

use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\joinup\Traits\NodeTrait;

/**
 * Behat step definitions for testing discussions.
 */
class DiscussionSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use RdfEntityTrait;
  use NodeTrait;

  /**
   * Checks if the given discussion belongs to the given collection.
   *
   * If there are multiple discussions or collections with the same name, then
   * only the first one is checked.
   *
   * @param string $title
   *   The title of the discussion to check.
   * @param string $collection
   *   The name of the collection to check.
   *
   * @throws \Exception
   *   Thrown when a discussion with the given title does not exist.
   *
   * @Then the :collection collection has a discussion titled :title
   */
  public function assertOgMembership($title, $collection) {
    $collection = $this->getRdfEntityByLabel($collection, 'collection');

    $discussions = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['title' => $title]);
    /** @var \Drupal\node\NodeInterface $discussion */
    $discussion = reset($discussions);

    if (empty($discussion)) {
      throw new \Exception("The discussion titled '$title' was not found.");
    }

    /** @var \Drupal\og\Plugin\Field\FieldType\OgStandardReferenceItem $group */
    foreach ($discussion->get(OgGroupAudienceHelper::DEFAULT_FIELD) as $group) {
      if ($group->getValue()['target_id'] == $collection->id()) {
        // Test passes.
        return;
      }
    }

    throw new \Exception("The discussion '$title' is not associated with collection '{$collection->label()}'.");
  }

  /**
   * Navigates to the canonical page display of a discussion.
   *
   * @param string $title
   *   The name of the discussion.
   *
   * @When (I )go to the :title discussion
   * @When (I )visit the :title discussion
   */
  public function visitDiscussion($title) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->getNodeByTitle($title, 'discussion');
    $this->visitPath($node->url());
  }

}
