<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Behat\Mink\Exception\ElementNotFoundException;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\joinup\Traits\NodeTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\og\OgGroupAudienceHelperInterface;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * Behat step definitions for interacting with groups.
 */
class JoinupGroupContext extends RawDrupalContext {

  use NodeTrait;
  use RdfEntityTrait;

  /**
   * Checks if the given node belongs to the given group.
   *
   * If there are multiple nodes or groups with the same name, then only
   * the first one is checked.
   *
   * @param string $group_label
   *   The name of the collection or solution to check.
   * @param string $group_type
   *   The type of the group.
   * @param string $content_title
   *   The title of the node to check.
   *
   * @throws \Exception
   *   Thrown when a node with the given title doesn't exist.
   *
   * @Then the :group_label :group_type should have a custom page titled :group_title
   * @Then the :group_label :group_type should have a community content titled :group_title
   */
  public function assertNodeOgMembership(string $group_label, string $group_type, string $content_title): void {
    $group = $this->getRdfEntityByLabel($group_label, $group_type);
    $node = $this->getNodeByTitle($content_title);
    if ($node->get(OgGroupAudienceHelperInterface::DEFAULT_FIELD)->target_id !== $group->id()) {
      throw new \Exception("The node '$content_title' is not associated with collection '{$group->label()}'.");
    }
  }

  /**
   * Asserts that a group menu link points to a resource outside the group.
   *
   * @param string $link_label
   *   The link text.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   The the link is not found in page.
   *
   * @Then the link :link_label points outside group
   */
  public function assertLinkPointsOutsideGroup(string $link_label): void {
    $session = $this->getSession();
    $page = $session->getPage();
    if (!$link = $page->findLink($link_label)) {
      throw new ElementNotFoundException($session, 'link', $link_label, 'label');
    }
    if (!$link->hasClass('group-menu-link-external')) {
      throw new ExpectationFailedException("Link '{$link_label}' should point to a resource outside the current group but it doesn't.");
    };
  }

}
