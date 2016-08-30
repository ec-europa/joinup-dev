<?php

/**
 * @file
 * Contains \DocumentSubContext.
 */

use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\joinup\Traits\NodeTrait;

/**
 * Behat step definitions for testing documents.
 */
class DocumentSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use RdfEntityTrait;
  use NodeTrait;

  /**
   * Checks if the given document belongs to the given parent rdf entity.
   *
   * If there are multiple documents or parents with the same name, then
   * only the first one is checked.
   *
   * @param string $parent
   *   The name of the collection to check.
   * @param string $bundle
   *   The bundle of the parent.
   * @param string $document
   *   The title of the document to check.
   *
   * @throws \Exception
   *   Thrown when a document with the given title does not exist.
   *
   * @Then the :parent :bundle has a document titled :title
   */
  public function assertOgMembership($parent, $bundle, $document) {
    $parent = $this->getRdfEntityByLabel($parent, $bundle);

    $documents = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['title' => $document]);
    /** @var \Drupal\node\NodeInterface $document */
    $document = reset($documents);

    if (empty($document)) {
      throw new \Exception("The document titled '$document' was not found.");
    }

    /** @var \Drupal\og\Plugin\Field\FieldType\OgStandardReferenceItem $group */
    foreach ($document->get(OgGroupAudienceHelper::DEFAULT_FIELD) as $group) {
      if ($group->getValue()['target_id'] == $parent->id()) {
        // Test passes.
        return;
      }
    }

    throw new \Exception("The document '$document' is not associated with the '{$parent->label()}' {$bundle}.");
  }

  /**
   * Navigates to the canonical page display of a document.
   *
   * @param string $title
   *   The name of the document.
   *
   * @When (I )go to the :title document
   * @When (I )visit the :title document
   */
  public function visitDocument($title) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->getNodeByTitle($title, 'document');
    $this->visitPath($node->url());
  }

}
