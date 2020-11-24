<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\joinup\Traits\NodeTrait;
use Drupal\joinup\Traits\TraversingTrait;
use PHPUnit\Framework\Assert;

/**
 * Behat step definitions to test common community content functionality.
 */
class JoinupCommunityContentContext extends RawDrupalContext {

  use NodeTrait;
  use TraversingTrait;

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
   * Asserts that a node entity does not have a publication date.
   *
   * @param string $title
   *   The title of the content.
   * @param string $type
   *   The type of the content.
   *
   * @Then the :title :type should not have a publication date
   */
  public function assertDifferentCreatedTimeWithUnpublishedVersion(string $title, string $type): void {
    // The Publication Date module sets a default value to the publication date
    // for nodes that have never been published.
    $node = $this->getNodeByTitle($title, $type);
    Assert::assertEquals(PUBLICATION_DATE_DEFAULT, $node->published_at->value);
  }

  /**
   * Checks that content has a publication date different from the created date.
   *
   * @param string $title
   *   The title of the content.
   * @param string $type
   *   The type of the content.
   *
   * @Then the publication date of the :title :type should not be equal to the created date
   */
  public function assertDifferentPublishedCreatedTime(string $title, string $type): void {
    $node = $this->getNodeByTitle($title, $type);
    Assert::assertNotEmpty($node->published_at->value);
    Assert::assertNotEquals($node->created->value, $node->published_at->value);
  }

  /**
   * Checks that a content's publication date same as the created date.
   *
   * @param string $title
   *   The title of the content.
   * @param string $type
   *   The type of the content.
   *
   * @Then the publication date of the :title :type should be equal to the created date
   */
  public function assertSamePublishedCreatedTime(string $title, string $type): void {
    $node = $this->getNodeByTitle($title, $type);
    Assert::assertNotEmpty($node->published_at->value);
    Assert::assertEquals($node->created->value, $node->published_at->value);
  }

  /**
   * Checks that a content has the same publication date with another version.
   *
   * @param string $title
   *   The title of the content.
   * @param string $type
   *   The type of the content.
   * @param string $published
   *   Whether to check for the last published or unpublished version.
   *   Possible options are 'published' and 'unpublished'.
   *
   * @throws \InvalidArgumentException
   *   Thrown if the $published variable does not have an acceptable value.
   * @throws \Exception
   *   Thrown if there was no previous revision found.
   *
   * @Then the publication date of the :title :type should be equal to the last :published version's
   */
  public function assertEqualRevisionsPublicationTime(string $title, string $type, string $published): void {
    if (!in_array($published, ['published', 'unpublished'])) {
      throw new \InvalidArgumentException("Only 'published' and 'unpublished' values are allowed for the 'published' flag.");
    }
    $published = $published === 'published';

    $node = $this->getNodeByTitle($title, $type);
    $node_revisions = $this->getNodeRevisionIdsList($title, $type, $published);
    $revision_id = reset($node_revisions);
    // In case the revision id is the same as the current node, then a node with
    // the same status was requested. Thus, pick the next id from the list.
    if ($node->getRevisionId() == $revision_id) {
      $revision_id = reset($node_revisions);
    }

    if (empty($revision_id)) {
      throw new \Exception("There was no revision found with the given criteria.");
    }
    $revision = \Drupal::entityTypeManager()->getStorage('node')->loadRevision($revision_id);
    Assert::assertEquals($node->published_at->value, $revision->published_at->value);
  }

  /**
   * Navigates to the edit or delete form of a community content.
   *
   * @param string $action
   *   The action. Either 'edit' or 'delete'.
   * @param string $title
   *   The title of the community content.
   * @param string $bundle
   *   The community content type. Usually one of 'discussion', document',
   *   'event', news', but can in fact be any node type.
   *
   * @throws \InvalidArgumentException
   *   Thrown when an invalid action is passed.
   * @throws \Drupal\Core\Entity\EntityMalformedException
   *   Thrown when the form URL cannot be generated for the community content.
   *
   * @When I go to the :action form of the :title :bundle
   * @When I visit the :action form of the :title :bundle
   */
  public function visitNodeForm(string $action, string $title, string $bundle): void {
    if (!in_array($action, ['edit', 'delete'])) {
      throw new \InvalidArgumentException('Only "edit" and "delete" actions are allowed.');
    }
    $node = $this->getNodeByTitle($title, $bundle);
    $path = $node->toUrl("{$action}-form")->getInternalPath();
    $this->visitPath($path);
  }

  /**
   * Update one of the date properties of a node.
   *
   * @param string $field_name
   *   The date field/property name.
   * @param string $title
   *   The node label.
   * @param string $bundle
   *   The node label.
   * @param string $new_date
   *   The new date property. The format should be acceptable by the strtotime()
   *   function. A full format example is "Thu, 26 Dec 2019 14:00:00 +0100". The
   *   above includes also "+0100" which represents the timezone.
   *
   * @see strtotime()
   *
   * @Given the :field_name date of the :title :bundle is :new_date
   */
  public function updateNodeDateProperty(string $field_name, string $title, string $bundle, string $new_date): void {
    if (!($time = strtotime($new_date))) {
      throw new \Exception("{$new_date} could not be converted to string.");
    }
    $this->getNodeByTitle($title, $bundle)
      ->set($field_name, $time)
      ->save();
  }

}
