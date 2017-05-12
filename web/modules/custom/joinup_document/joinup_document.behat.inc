<?php

/**
 * @file
 * Contains \DocumentSubContext.
 */

use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\DrupalExtension\Hook\Scope\BeforeNodeCreateScope;
use Drupal\joinup\Traits\FileTrait;
use Drupal\joinup\Traits\NodeTrait;

/**
 * Behat step definitions for testing documents.
 */
class DocumentSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use FileTrait;
  use NodeTrait;

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

  /**
   * Handles the file field for document nodes.
   *
   * @param \Drupal\DrupalExtension\Hook\Scope\BeforeNodeCreateScope $scope
   *   An object containing the entity properties and fields that are to be used
   *   for creating the node as properties on the object.
   *
   * @BeforeNodeCreate
   */
  public function massageDocumentFieldsBeforeNodeCreate(BeforeNodeCreateScope $scope) {
    $node = $scope->getEntity();

    if ($node->type !== 'document' || empty($node->file)) {
      return;
    }

    $type = $node->{'file type'} ?? 'remote';
    if ($type === 'remote') {
      $node->field_file = $node->file;
    }
    else {
      $node->field_file = $this->createFile($node->file, $this->getMinkParameter('files_path'))->id();
    }

    unset($node->file);
  }

}
