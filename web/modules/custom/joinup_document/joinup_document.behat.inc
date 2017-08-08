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
use Drupal\joinup\Traits\TraversingTrait;

/**
 * Behat step definitions for testing documents.
 */
class DocumentSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use FileTrait;
  use NodeTrait;
  use TraversingTrait;

  /**
   * {@inheritdoc}
   */
  public function cleanUsers() {
    try {
      parent::cleanUsers();
    }
    catch (Exception $e) {
      throw new Exception($e->getMessage());
    }
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

  /**
   * Handles the file field for document nodes.
   *
   * @param \Drupal\DrupalExtension\Hook\Scope\BeforeNodeCreateScope $scope
   *   An object containing the entity properties and fields that are to be used
   *   for creating the node as properties on the object.
   *
   * @BeforeNodeCreate
   */
  public static function massageDocumentFieldsBeforeNodeCreate(BeforeNodeCreateScope $scope) {
    $node = $scope->getEntity();

    if ($node->type !== 'document' || empty($node->file)) {
      return;
    }

    $type = isset($node->{'file type'}) ? $node->{'file type'} : 'remote';
    if ($type !== 'remote') {
      // If the file is local we want to copy it from the fixtures into the file
      // system and register it in the DocumentSubContext so it can be cleaned
      // up after the scenario ends. Perform a small dance to get access to the
      // context class from inside this static callback.
      /** @var \Behat\Behat\Context\Environment\InitializedContextEnvironment $environment */
      $environment = $scope->getEnvironment();
      /** @var \DocumentSubContext $context */
      $context = $environment->getContext(self::class);
      $node->file = $context->createFile($node->file)->id();
    }

    unset($node->{'file type'});
  }

  /**
   * Asserts that a document tile has a link to download its attached file.
   *
   * @param string $title
   *   The document title.
   *
   * @throws \Exception
   *   Thrown when the download link is not found.
   *
   * @Then the download link is shown in the :title document tile
   */
  public function assertDocumentTileHasDownloadLink($title) {
    $tile = $this->getTileByHeading($title);

    $file_url_service = \Drupal::service('file_url.handler');
    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->getNodeByTitle($title, 'document');
    /** @var \Drupal\file\FileInterface $file */
    $file = $file_url_service::urlToFile($node->get('field_file')->first()->target_id);

    $link = $tile->findLink('Download');
    if (!$link) {
      throw new \Exception("No download link found in the '$title' tile.");
    }

    // @see \Drupal\file_url\Plugin\Field\FieldFormatter\FileUrlFormatter::viewElements()
    $expected_href = file_url_transform_relative(file_create_url($file->getFileUri()));
    \PHPUnit_Framework_Assert::assertEquals($expected_href, $link->getAttribute('href'), 'The download link is not pointing to the correct file.');
  }

}
