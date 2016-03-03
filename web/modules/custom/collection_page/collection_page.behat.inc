<?php

/**
 * @file
 * Contains \CollectionPageSubContext.
 */

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;

/**
 * Behat step definitions for testing collections.
 */
class CollectionPageSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {
  /**
   * Test collections.
   *
   * @var \Drupal\node\Entity\Node[]
   */
  protected $collectionPages = [];

  /**
   * The bundle type of the content entity.
   *
   * @var string
   */
  protected $bundle = 'collection_page';

  /**
   * Creates content of a given type provided in the form.
   * @codingStandardsIgnoreStart
   * | title    | author     | status | created           |
   * | My title | Joe Editor | 1      | 2014-10-17 8:00am |
   * | ...      | ...        | ...    | ...               |
   * @codingStandardsIgnoreEnd
   *
   * @Given content of type "Collection Page":
   */
  public function createNodes(TableNode $nodes) {
    foreach ($nodes->getHash() as $hash) {
      $node = (object) $hash;
      $node->type = $this->bundle;
      $this->nodeCreate($node);
      $this->collectionPages[] = $node->nid;
    }
  }

  /**
   * Checks the existence of a collection page.
   *
   * @param int $title
   *   The title of the collection page.
   *
   * @throws \Exception
   *   Thrown when the entity is not found.
   *
   * @Then I should have a collection page titled :title
   */
  public function assertCollectionPage($title) {
    $actual = \Drupal::entityQuery('node')
      ->condition('title', $title)
      ->count()
      ->execute();
    if (!$actual) {
      throw new \Exception("Entity titled '$title' was not found.");
    }
  }

  /**
   * Remove any created collections.
   *
   * @AfterScenario
   */
  public function cleanCollections() {
    // Remove any collection_pages that were created.
    $storage_handler = \Drupal::entityManager()->getStorage('node');
    $entities = $storage_handler->loadMultiple($this->collectionPages);
    $storage_handler->delete($entities);
  }

}
