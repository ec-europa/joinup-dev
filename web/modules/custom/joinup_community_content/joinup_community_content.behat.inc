<?php

/**
 * @file
 * Contains \SolutionSubContext.
 */

use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\NodeTrait;
use Drupal\joinup\Traits\TraversingTrait;
use PHPUnit\Framework\Assert;

/**
 * Behat step definitions to test common community content functionality.
 */
class JoinupCommunityContentSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use TraversingTrait;
  use NodeTrait;

  /**
   * Asserts that a tile is not marked as shared from another collection.
   *
   * @param string $heading
   *   The heading of the tile.
   *
   * @throws \Exception
   *   Thrown when the tile is marked as shared.
   *
   * @Then the :heading tile should not be marked as shared
   */
  public function assertTileNotMarkedAsShared($heading) {
    $element = $this->getTileByHeading($heading);

    if ($element->find('css', '.icon--shared')) {
      throw new \Exception("The tile '$heading' is marked as shared, but it shouldn't be.");
    }
  }

  /**
   * Asserts that a tile is marked as shared from a certain collection.
   *
   * @param string $heading
   *   The heading of the tile.
   * @param string $collection
   *   The collection that the content was shared from.
   *
   * @throws |Exception
   *   Thrown when the tile is not marked as shared, or it's marked as shared
   *   from the wrong collection.
   *
   * @Then the :heading tile should be marked as shared from :collection
   */
  public function assertTileMarkedAsShared($heading, $collection) {
    $element = $this->getTileByHeading($heading);

    $share = $element->find('css', '.icon--shared');
    if (!$share) {
      throw new \Exception("The tile '$heading' is not marked as shared, but it should be.");
    }

    /** @var \Behat\Mink\Element\NodeElement $parent */
    $parent = $share->find('xpath', "/parent::div[@class and contains(concat(' ', normalize-space(@class), ' '), ' listing__stat ')]");
    if (!$parent) {
      throw new \Exception("The tile '$heading' has faulty markup for the shared content visual cue.");
    }

    $title_attribute = $parent->getAttribute('title');
    if ($title_attribute !== "Shared from $collection") {
      throw new \Exception("The tile '$heading' is marked as shared from $title_attribute, but it should be '$collection'.");
    }
  }

  /**
   * Asserts that the created time differs between published and unpublished.
   *
   * @param string $title
   *   The title of the content.
   * @param string $type
   *   The type of the content.
   *
   * @Given the created date of the :title :type should be different than its unpublished version
   */
  public function assertDifferentCreatedTimeWithUnpublishedVersion($title, $type) {
    $node = $this->getNodeByTitle($title, $type);
    $last_revision = $this->getLastNodeVersion($title, $type, FALSE);
    if (empty($last_revision)) {
      throw new Exception("Could not locate a previously unpublished version for the '{$title}' {$type} content.");
    }

    Assert::assertNotEquals($last_revision->getCreatedTime(), $node->getCreatedTime());
  }

  /**
   * Asserts that the created time is the same with the last published version.
   *
   * @param string $title
   *   The title of the content.
   * @param string $type
   *   The type of the content.
   *
   * @Given the created date of the :title :type should be the same as the last published version
   */
  public function assertSameCreatedTimeWithLastVersion($title, $type) {
    $node = $this->getNodeByTitle($title, $type);
    $last_revision = $this->getLastNodeVersion($title, $type, TRUE);
    if (empty($last_revision)) {
      throw new Exception("Could not locate a previously published version for the '{$title}' {$type} content.");
    }

    Assert::assertEquals($last_revision->getCreatedTime(), $node->getCreatedTime());
  }

}
