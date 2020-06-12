<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\joinup\Traits\EntityTrait;

/**
 * Behat step definitions for interacting with the Joinup front page.
 */
class JoinupFrontPageContext extends RawDrupalContext {

  use EntityTrait;

  /**
   * Loads and pins entities in the front page.
   *
   * @param string $type
   *   The entity type ID.
   * @param \Behat\Gherkin\Node\TableNode $table
   *   A list of titles.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the user has entered an invalid type.
   *
   * @Given the following :type entities are pinned to the front page:
   */
  public function givenPinnedEntities(string $type, TableNode $table): void {
    if (!in_array($type, ['rdf', 'content'])) {
      throw new \InvalidArgumentException('Only "rdf" and "content" are allowed as type.');
    }

    /** @var \Drupal\joinup_front_page\FrontPageMenuHelperInterface $front_page_helper */
    $front_page_helper = \Drupal::service('joinup_front_page.front_page_helper');
    $type = $type === 'content' ? 'node' : 'rdf_entity';
    foreach ($table->getColumnsHash() as $row) {
      $entity = $this->getEntityByLabel($type, $row['title']);
      $front_page_helper->pinToFrontPage($entity);
    }
  }

}
