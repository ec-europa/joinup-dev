<?php

/**
 * @file
 * Contains \JoinupFrontPageSubContext.
 */

declare(strict_types = 1);

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\EntityTrait;
use Drupal\joinup\Traits\NodeTrait;
use Drupal\joinup\Traits\UserTrait;
use Drupal\joinup_discussion\Form\InviteToDiscussionForm;
use Drupal\joinup_invite\Entity\Invitation;
use Drupal\joinup_invite\InvitationMessageHelperInterface;

/**
 * Behat step definitions for testing invitations.
 */
class JoinupFrontPageSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use EntityTrait;

  /**
   * Loads and pins entities in the front page.
   *
   * @param string $type
   *   The entity type id.
   * @param \Behat\Gherkin\Node\TableNode $table
   *   A list of titles.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the user has entered an invalid type.
   *
   * @Given site pinned :type entities:
   */
  public function givenPinnedEntities(string $type, TableNode $table): void {
    if (!in_array($type, ['rdf', 'content'])) {
      throw new InvalidArgumentException('Only "rdf" and "content" are allowed as type.');
    }

    /** @var \Drupal\joinup_front_page\FrontPageMenuHelperInterface $front_page_helper */
    $front_page_helper = \Drupal::service('joinup_front_page.front_page_helper');
    $type = $type === 'content' ? 'node' : 'rdf_entity';
    foreach ($table->getColumnsHash() as $row) {
      $entity = $this->getEntityByLabel($type, $row['title']);
      $front_page_helper->pinSiteWide($entity);
    }
  }

}
