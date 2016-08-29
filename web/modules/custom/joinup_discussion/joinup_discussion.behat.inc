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
   * Checks if the given discussion belongs to the given parent rdf entity.
   *
   * If there are multiple discussions or parents with the same name, then
   * only the first one is checked.
   *
   * @param string $discussion
   *   The title of the discussion to check.
   * @param string $bundle
   *   The bundle of the parent.
   * @param string $parent
   *   The name of the collection to check.
   *
   * @throws \Exception
   *   Thrown when a discussion with the given title does not exist.
   *
   * @Then the :collection :bundle has a discussion titled :title
   */
  public function assertOgMembership($discussion, $bundle, $parent) {
    $parent = $this->getRdfEntityByLabel($parent, $bundle);

    $discussions = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['title' => $discussion]);
    /** @var \Drupal\node\NodeInterface $discussion */
    $discussion = reset($discussions);

    if (empty($discussion)) {
      throw new \Exception("The discussion titled '$discussion' was not found.");
    }

    /** @var \Drupal\og\Plugin\Field\FieldType\OgStandardReferenceItem $group */
    foreach ($discussion->get(OgGroupAudienceHelper::DEFAULT_FIELD) as $group) {
      if ($group->getValue()['target_id'] == $parent->id()) {
        // Test passes.
        return;
      }
    }

    throw new \Exception("The discussion '$discussion' is not associated with the '{$parent->label()}' {$bundle}.");
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
