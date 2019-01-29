<?php

/**
 * @file
 * Contains \JoinupCommunityContentSubContext.
 */

declare(strict_types = 1);

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
  public function assertTileNotMarkedAsShared(string $heading): void {
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
  public function assertTileMarkedAsShared(string $heading, string $collection): void {
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
   * @param string $published
   *   Whether to request the published or unpublished version. Allowed values
   *   are 'published' and 'unpublished'.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $published has an invalid value.
   * @throws \Exception
   *   Thrown if the expected and the actual values match.
   *
   * @Given the latest version of the :title :type should have a different created date than the last :published version
   */
  public function assertDifferentCreatedTimeWithUnpublishedVersion(string $title, string $type, string $published): void {
    if (!in_array($published, ['published', 'unpublished'])) {
      throw new Exception('Only "published" and "unpublished" values are allowed for variable $published.');
    }
    $published = $published === 'published';
    $revision_list = $this->getNodeRevisionIdsList($title, $type);
    $revision_id = reset($revision_list);
    $latest_revision = \Drupal::entityTypeManager()->getStorage('node')->loadRevision($revision_id);

    $previous_revision_list = $this->getNodeRevisionIdsList($title, $type, $published);
    // If both requested versions are of the same status, the pre-last revision
    // is the correct one.
    $index = array_search($revision_id, $previous_revision_list);
    if ($index !== FALSE) {
      unset($previous_revision_list[$index]);
    }
    $previous_id = reset($previous_revision_list);
    $previous_revision = \Drupal::entityTypeManager()->getStorage('node')->loadRevision($previous_id);

    Assert::assertNotEquals($latest_revision->getCreatedTime(), $previous_revision->getCreatedTime());
  }

  /**
   * Asserts that the created time is the same with the last published version.
   *
   * @param string $title
   *   The title of the content.
   * @param string $type
   *   The type of the content.
   * @param string $published
   *   Whether to request the published or unpublished version. Allowed values
   *   are 'published' and 'unpublished'.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $published has an invalid value.
   * @throws \Exception
   *   Thrown if the expected and the actual values do not match.
   *
   * @Given the latest version of the :title :type should have the same created date as the last :published version
   */
  public function assertSameCreatedTimeWithLastVersion(string $title, string $type, string $published): void {
    if (!in_array($published, ['published', 'unpublished'])) {
      throw new InvalidArgumentException('Only "published" and "unpublished" values are allowed for variable $published.');
    }
    $published = $published === 'published';
    $revision_list = $this->getNodeRevisionIdsList($title, $type);
    $revision_id = reset($revision_list);
    $latest_revision = \Drupal::entityTypeManager()->getStorage('node')->loadRevision($revision_id);

    $previous_revision_list = $this->getNodeRevisionIdsList($title, $type, $published);
    // If both requested versions are of the same status, the pre-last revision
    // is the correct one.
    $index = array_search($revision_id, $previous_revision_list);
    if ($index !== FALSE) {
      unset($previous_revision_list[$index]);
    }
    $previous_id = reset($previous_revision_list);
    $previous_revision = \Drupal::entityTypeManager()->getStorage('node')->loadRevision($previous_id);

    Assert::assertEquals($latest_revision->getCreatedTime(), $previous_revision->getCreatedTime());
  }

}
