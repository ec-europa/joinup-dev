<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Behat\Mink\Exception\ElementNotFoundException;
use Drupal\Core\Url;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\joinup\Traits\EntityTrait;
use Drupal\joinup\Traits\NodeTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\og\OgMembershipInterface;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * Behat step definitions for interacting with groups.
 */
class JoinupGroupContext extends RawDrupalContext {

  use EntityTrait;
  use NodeTrait;
  use RdfEntityTrait;

  /**
   * Navigates to the members page of the given group.
   *
   * @param string $label
   *   The name of the group.
   *
   * @When I go to the members page of :label
   * @When I am on the members page of :label
   */
  public function visitMembersPage(string $label): void {
    $group = $this->getRdfEntityByLabel($label);
    $url = Url::fromRoute('entity.rdf_entity.member_overview', [
      'rdf_entity' => $group->id(),
    ]);
    $this->visitPath($url->toString());
  }

  /**
   * Navigates to the membership permissions table of the given group.
   *
   * @param string $label
   *   The name of the group.
   *
   * @When I go to the member(ship) permissions table of :label
   */
  public function visitMembershipPermissionsTable(string $label): void {
    $group = $this->getRdfEntityByLabel($label);
    $url = Url::fromRoute('joinup_group.membership_permissions_info', [
      'rdf_entity' => $group->id(),
    ]);
    $this->visitPath($url->toString());
  }

  /**
   * Navigates to the about page of a community or solution.
   *
   * @param string $label
   *   The label of the group for which to visit the about page.
   *
   * @Given I go to the about page of :label
   */
  public function visitAboutPage(string $label): void {
    $group = $this->getEntityByLabel('rdf_entity', $label);
    $url = Url::fromRoute('entity.rdf_entity.about_page', [
      'rdf_entity' => $group->id(),
    ]);
    $this->visitPath($url->toString());
  }

  /**
   * Checks if the given node belongs to the given group.
   *
   * If there are multiple nodes or groups with the same name, then only
   * the first one is checked.
   *
   * @param string $group_label
   *   The name of the community or solution to check.
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
      throw new \Exception("The node '$content_title' is not associated with community '{$group->label()}'.");
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

  /**
   * Checks that a cookie is set that tracks which group a user wants to join.
   *
   * An anonymous user can join a group only after they sign in or register. A
   * cookie is set which will track which group the user wanted to join.
   *
   * @param string $label
   *   The label of the group the user wants to join.
   *
   * @Then a cookie should be set that allows me to join :label after authenticating
   */
  public function assertAnonGroupJoinTrackingCookiePresent(string $label): void {
    $cookie = $this->getSession()->getCookie('join_group');
    $entity = $this->getRdfEntityByLabelUnchanged($label);
    Assert::assertEquals($entity->id(), $cookie, "A cookie was expected to track that an anonymous user wants to join the '$label' group.");
  }

  /**
   * Checks that no cookie is set that tracks which group a user wants to join.
   *
   * @Then the cookie that tracks which group I want to join should not be set
   */
  public function assertNoAnonGroupJoinTrackingCookiePresent(): void {
    Assert::assertEmpty($this->getSession()->getCookie('join_group'), 'No cookie should be set that tracks which group an anonymous user wants to join.');
  }

  /**
   * Checks the number of members in a given group.
   *
   * In OG parlance a group member can be any kind of entity, but this only
   * checks which users are members of the group.
   *
   * @param string $label
   *   The name of the group to check.
   * @param string $type
   *   The group type, either 'community' or 'solution'.
   * @param int $number
   *   The expected number of members in the group.
   * @param string $membership_state
   *   The state of the membership. Can be 'active', 'pending' or 'blocked'.
   *
   * @throws \Exception
   *   Thrown when the number of members does not not match the expectation.
   *
   * @Then the :label :type should have :number :membership_state member(s)
   */
  public function assertMemberCount(string $label, string $type, int $number, string $membership_state): void {
    $states = [
      OgMembershipInterface::STATE_ACTIVE,
      OgMembershipInterface::STATE_PENDING,
      OgMembershipInterface::STATE_BLOCKED,
    ];

    if (!in_array($membership_state, $states)) {
      throw new \Exception("Invalid membership state '{$membership_state}' found.");
    }

    /** @var \Drupal\joinup_group\Entity\GroupInterface $entity */
    $entity = $this->getRdfEntityByLabelUnchanged($label, $type);
    $actual = $entity->getMemberCount([$membership_state]);

    if ($actual != $number) {
      throw new \Exception("Wrong number of {$membership_state} members. Expected number: $number, actual number: $actual.");
    }
  }

}
