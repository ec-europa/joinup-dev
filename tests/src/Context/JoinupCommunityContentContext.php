<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\DrupalExtension\Hook\Scope\AfterNodeCreateScope;
use Drupal\DrupalExtension\Hook\Scope\BeforeNodeCreateScope;
use Drupal\joinup\Traits\NodeTrait;
use Drupal\joinup\Traits\TestingEntitiesTrait;
use Drupal\joinup\Traits\TraversingTrait;
use Drupal\joinup_community_content\CommunityContentHelper;
use Drupal\joinup_group\Entity\PinnableGroupContentInterface;
use Drupal\joinup_group\Exception\MissingGroupException;
use Drupal\joinup_publication_date\Entity\EntityPublicationTimeInterface;
use Drupal\node\Entity\Node;
use Drupal\rdf_taxonomy\Entity\RdfTerm;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * Behat step definitions to test common community content functionality.
 */
class JoinupCommunityContentContext extends RawDrupalContext {

  use NodeTrait;
  use TestingEntitiesTrait;
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
   *
   * @throws \InvalidArgumentException
   *   Thrown when the node does not have a publication date field.
   */
  public function assertDifferentCreatedTimeWithUnpublishedVersion(string $title, string $type): void {
    // The Publication Date module sets a default value to the publication date
    // for nodes that have never been published.
    $node = $this->getNodeByTitle($title, $type);
    if (!$node instanceof EntityPublicationTimeInterface) {
      throw new \InvalidArgumentException('Node does not have a publication date field.');
    }
    Assert::assertEmpty($node->getPublicationTime());
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
   *
   * @throws \InvalidArgumentException
   *   Thrown when the node does not have a publication date field.
   */
  public function assertDifferentPublishedCreatedTime(string $title, string $type): void {
    $node = $this->getNodeByTitle($title, $type);
    if (!$node instanceof EntityPublicationTimeInterface) {
      throw new \InvalidArgumentException('Node does not have a publication date field.');
    }
    Assert::assertNotEmpty($node->getPublicationTime());
    Assert::assertNotEquals($node->created->value, $node->getPublicationTime());
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
   *
   * @throws \InvalidArgumentException
   *   Thrown when the node does not have a publication date field.
   */
  public function assertSamePublishedCreatedTime(string $title, string $type): void {
    $node = $this->getNodeByTitle($title, $type);
    if (!$node instanceof EntityPublicationTimeInterface) {
      throw new \InvalidArgumentException('Node does not have a publication date field.');
    }
    Assert::assertNotEmpty($node->getPublicationTime());

    // Depending on the performance of the test environment it is possible that
    // a small amount of time has passed between the moment the node was created
    // and the moment it was published. We allow a grace period of 5 seconds to
    // account for the difference between the created and the publication date.
    Assert::assertTrue(abs((int) $node->created->value - (int) $node->getPublicationTime()) < 5);
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
   *   Thrown if the $published variable does not have an acceptable value, or
   *   if the entity with the given label doesn't have a publication date field.
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
    if (!$node instanceof EntityPublicationTimeInterface) {
      throw new \InvalidArgumentException('Node does not have a publication date field.');
    }
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
    if (!$revision instanceof EntityPublicationTimeInterface) {
      throw new \InvalidArgumentException('Node revision does not have a publication date field.');
    }
    Assert::assertEquals($node->getPublicationTime(), $revision->getPublicationTime());
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

  /**
   * Pins newly created nodes.
   *
   * This checks if the "Pinned" property is set for a newly created node, and
   * sets the pinned status accordingly. This is not done as part of the regular
   * node creation since this data is not part of the node but is stored in a
   * metadata entity.
   *
   * @param \Drupal\DrupalExtension\Hook\Scope\AfterNodeCreateScope $scope
   *   The Behat hook scope object containing the metadata of the node that was
   *   created.
   *
   * @throws \Exception
   *   Thrown when the node is marked to be pinned but is not associated with a
   *   group.
   *
   * @AfterNodeCreate
   */
  public function pinCommunityContentInGroup(AfterNodeCreateScope $scope) {
    $node = $scope->getEntity();

    $is_pinned = in_array(strtolower((string) ($node->pinned ?? '')), [
      'y',
      'yes',
    ]);
    $nid = $node->nid ?? NULL;
    if ($is_pinned && $nid) {
      /** @var \Drupal\node\NodeInterface $entity */
      if ($entity = Node::load((int) $nid)) {
        if ($entity instanceof PinnableGroupContentInterface) {
          try {
            $group = $entity->getGroup();
            $entity->pin($group);
          }
          catch (MissingGroupException $e) {
            throw new \Exception("The '{$node->title}' community content cannot be pinned since it does not belong to a group.");
          }
        }
      }
    }
  }

  /**
   * Asserts that a sequence of HTML markup exists inside a comment.
   *
   * The comment is identified by its '1 based' number. Numbers are starting
   * from 1 and are assigned in the order comments are shown on the page,
   * regardless of their indentation.
   *
   * @param string $comment_number
   *   The '1 based' number of the comment in the page.
   * @param string $markup
   *   The piece of markup to be checked.
   *
   * @Then (the )comment #:comment_number should contain the markup :markup
   */
  public function assertCommentContainsMarkup(string $comment_number, string $markup): void {
    $comment = $this->getNumberedComment($comment_number - 1);
    $regex = '/' . preg_quote($markup, '/') . '/ui';
    Assert::assertRegExp($regex, $comment->getOuterHtml());
  }

  /**
   * Asserts that a comment has the given indentation.
   *
   * The comment is identified by its '1 based' number. Numbers are starting
   * from 1 and are assigned in the order comments are shown on the page,
   * regardless of their indentation. The indentation is a '0 based' integer.
   *
   * @param int $comment_number
   *   The '1 based' number of the comment in the page.
   * @param int $indent
   *   The '0 based' indentation value.
   *
   * @Then (the )comment #:comment_number indent is :indent
   */
  public function assertCommentIndent(int $comment_number, int $indent): void {
    $comment = $this->getNumberedComment($comment_number - 1);
    $nested_comments = $comment->findAll('xpath', '/ancestor::div[contains(concat(" ", @class, " "), " indented ")]');
    Assert::assertCount($indent, $nested_comments);
  }

  /**
   * Asserts that a link exists within a comment.
   *
   * The comment is identified by its '1 based' number. Numbers are starting
   * from 1 and are assigned in the order comments are shown on the page,
   * regardless of their indentation.
   *
   * @param string $label
   *   The link's text.
   * @param int $comment_number
   *   The '1 based' number of the comment in the page.
   *
   * @Then I should see the link :label in comment #:comment_number
   */
  public function assertCommentContainsLink(string $label, int $comment_number): void {
    if (!$this->getNumberedComment($comment_number - 1)->findLink($label)) {
      throw new ElementNotFoundException($this->getSession(), 'link', 'text', $label);
    }
  }

  /**
   * Asserts that a link doesn't exist within a comment.
   *
   * The comment is identified by its '1 based' number. Numbers are starting
   * from 1 and are assigned in the order comments are shown on the page,
   * regardless of their indentation.
   *
   * @param string $label
   *   The link's text.
   * @param int $comment_number
   *   The '1 based' number of the comment in the page.
   *
   * @Then I should not see the link :label in comment #:comment_number
   */
  public function assertCommentNotContainsLink(string $label, int $comment_number): void {
    if ($this->getNumberedComment($comment_number - 1)->findLink($label)) {
      throw new ExpectationFailedException("Link '{$label}' exists in comment #{$comment_number} but it should not.");
    }
  }

  /**
   * Clicks a link within a comment.
   *
   * The comment is identified by its '1 based' number. Numbers are starting
   * from 1 and are assigned in the order comments are shown on the page,
   * regardless of their indentation.
   *
   * @param string $label
   *   The link's text.
   * @param int $comment_number
   *   The '1 based' number of the comment in the page.
   *
   * @Then I click :label in comment #:comment_number
   */
  public function clickCommentLink(string $label, int $comment_number): void {
    $comment = $this->getNumberedComment($comment_number - 1);
    $comment->clickLink($label);
  }

  /**
   * Returns the a comment element given its '0 based' index on the page.
   *
   * @param int $index
   *   The '0 based' index of the comment in page.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The comment node element.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   When there's no comment with the provided index on the current page.
   */
  protected function getNumberedComment(int $index): NodeElement {
    $session = $this->getSession();
    $comments = $session->getPage()->findAll('css', '.comment-item');
    if (!isset($comments[$index])) {
      throw new ElementNotFoundException($session, 'Comment #' . ($index + 1));
    }
    return $comments[$index];
  }

  /**
   * Sets a random topic for community content that misses one.
   *
   * For some tests the community content's topic has no relevance. Such tests
   * are allowed to omit an explicit topic. We're creating a dummy topic term,
   * together with its parent, just to satisfy data integrity and prevent form
   * validation errors.
   *
   * @param \Drupal\DrupalExtension\Hook\Scope\BeforeNodeCreateScope $scope
   *   An object containing the entity properties and fields that are to be used
   *   for creating the node as properties on the object.
   *
   * @BeforeNodeCreate
   */
  public function provideTopic(BeforeNodeCreateScope $scope) {
    $node = $scope->getEntity();

    // Only deal with community content.
    if (!in_array($node->type, CommunityContentHelper::BUNDLES, TRUE)) {
      return;
    }

    // A topic has been already set.
    $alias = 'topic';
    if (!empty($node->{$alias})) {
      return;
    }

    // Try, first, to get an existing topic term.
    if (!empty($this->entities['taxonomy_term'])) {
      foreach ($this->entities['taxonomy_term'] as $candidate_term) {
        if ($candidate_term->bundle() === 'topic' && !$candidate_term->get('parent')->isEmpty()) {
          $term = $candidate_term;
          break;
        }
      }
    }

    // Create a new topic term.
    if (!isset($term)) {
      $term = RdfTerm::create([
        'vid' => 'topic',
        'name' => $this->getRandom()->name(8, TRUE),
        'parent' => RdfTerm::create([
          'vid' => 'topic',
          'name' => $this->getRandom()->name(8, TRUE),
        ]),
      ]);
      $term->save();

      // Register the new terms to be cleaned-up after scenario.
      $this->entities['taxonomy_term'][$term->get('parent')->target_id] = $term->get('parent')->entity;
      $this->entities['taxonomy_term'][$term->id()] = $term;
    }

    $node->{$alias} = $term->label();
  }

}
