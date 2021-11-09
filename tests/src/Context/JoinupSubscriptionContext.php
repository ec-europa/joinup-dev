<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Drupal\Core\Url;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\DrupalExtension\TagTrait;
use Drupal\joinup\Traits\BrowserCapabilityDetectionTrait;
use Drupal\joinup\Traits\EntityTrait;
use Drupal\joinup\Traits\MailCollectorTrait;
use Drupal\joinup\Traits\MaterialDesignTrait;
use Drupal\joinup\Traits\OgTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\joinup\Traits\TraversingTrait;
use Drupal\joinup\Traits\UserTrait;
use Drupal\joinup\Traits\UtilityTrait;
use Drupal\joinup_community_content\CommunityContentHelper;
use Drupal\joinup_subscription\Entity\GroupContentSubscriptionMessageInterface;
use Drupal\joinup_subscription\JoinupDiscussionSubscriptionInterface;
use Drupal\joinup_subscription\JoinupSubscriptionsHelper;
use Drupal\message_digest\Traits\MessageDigestTrait;
use Drupal\og\OgMembershipInterface;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Assert;

/**
 * Behat step definitions for testing subscriptions.
 */
class JoinupSubscriptionContext extends RawDrupalContext {

  use BrowserCapabilityDetectionTrait;
  use EntityTrait;
  use MaterialDesignTrait;
  use MailCollectorTrait;
  use MessageDigestTrait;
  use OgTrait;
  use RdfEntityTrait;
  use UserTrait;
  use UtilityTrait;
  use TagTrait;
  use TraversingTrait;

  /**
   * A list of the available digest message intervals.
   *
   * This is hardcoded instead of being retrieved dynamically from the config so
   * that our tests will detect it if one of these intervals would unexpectedly
   * be removed from the code base.
   */
  const MESSAGE_INTERVALS = ['Daily', 'Weekly', 'Monthly'];

  /**
   * Navigates to the My subscriptions form of the given user.
   *
   * @param string|null $username
   *   The name of the user.
   *
   * @When I go to the subscription settings of :username
   * @When I go to my subscriptions
   */
  public function visitMySubscriptions(?string $username = NULL): void {
    if (!empty($username)) {
      $user = $this->getUserByName($username);
      $url = Url::fromRoute('joinup_subscription.subscriptions', [
        'user' => $user->id(),
      ]);
    }
    else {
      $url = Url::fromRoute('joinup_subscription.my_subscriptions');
    }
    $this->visitPath($url->toString());
  }

  /**
   * Navigates to the global subscribers report.
   *
   * @When I go to the global subscribers report
   */
  public function visitGlobalSubscribersReport(): void {
    $url = Url::fromRoute('joinup_subscription.subscribers_report');
    $this->visitPath($url->toString());
  }

  /**
   * Navigates to the subscribers report for the given group.
   *
   * @param string $group
   *   The name of the group.
   *
   * @When I go to the subscribers report for :group
   */
  public function visitSubscribersReport(string $group): void {
    $group_entity = $this->getRdfEntityByLabel($group);
    $url = Url::fromRoute('joinup_subscription.group_subscribers_report', [
      'rdf_entity' => $group_entity->id(),
    ]);
    $this->visitPath($url->toString());
  }

  /**
   * Subscribes the given users to the given discussion.
   *
   * @param \Behat\Gherkin\Node\TableNode $table
   *   A table with the keys 'username' and 'title'.
   *
   * @Given (the following )discussion subscriptions:
   */
  public function subscribeToDiscussion(TableNode $table): void {
    $subscription_service = $this->getDiscussionSubscriptionService();
    foreach ($table->getColumnsHash() as $values) {
      $user = $this->getUserByName($values['username']);
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $this->getEntityByLabel('node', $values['title'], 'discussion');
      // @todo Currently we only have subscriptions for discussions. Provide a
      //   lookup table for the flag ID once we have more.
      $subscription_service->subscribe($user, $entity, 'subscribe_discussions');
    }
  }

  /**
   * Subscribes the given users to the given group content bundles.
   *
   * Table format:
   * | collection   | user   | subscriptions                     |
   * | Collection A | user A | discussion, document, event, news |
   * | Collection B | user B | discussion, event                 |
   *
   * @param \Behat\Gherkin\Node\TableNode $subscription_table
   *   A table with the data for subscribing the users.
   * @param string $bundle
   *   The group bundle.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when a membership cannot be updated with the new subscriptions.
   * @throws \Exception
   *   Thrown if a membership is not found for a given user in a given
   *   collection.
   *
   * @Given (the following ):bundle content subscriptions:
   */
  public function subscribeToGroupContent(TableNode $subscription_table, string $bundle): void {
    foreach ($subscription_table->getColumnsHash() as $values) {
      $group = $this->getRdfEntityByLabel($values[$bundle], $bundle);
      $user = $this->getUserByName($values['user']);
      $membership = $this->getMembershipByGroupAndUser($group, $user, OgMembershipInterface::ALL_STATES);
      $subscriptions = [];
      $subscription_bundles = JoinupSubscriptionsHelper::SUBSCRIPTION_BUNDLES[$bundle];
      foreach ($this->explodeCommaSeparatedStepArgument(strtolower($values['subscriptions'])) as $subscription_bundle) {
        $subscription_bundle = static::translateBundle($subscription_bundle);
        $entity_type = NULL;
        foreach ($subscription_bundles as $entity_type_id => $bundles) {
          if (in_array($subscription_bundle, $bundles)) {
            $entity_type = $entity_type_id;
            break 1;
          }
        }
        Assert::assertNotEmpty($entity_type, "Unknown bundle $subscription_bundle.");
        $subscriptions[] = [
          'entity_type' => $entity_type,
          'bundle' => $subscription_bundle,
        ];
      }
      $membership->set('subscription_bundles', $subscriptions)->save();
    }
  }

  /**
   * Asserts the status of the save button in a subscription card.
   *
   * @param string $button
   *   The button label.
   * @param string $label
   *   The group name.
   * @param string $status
   *   The expected status. Possible values are 'enabled' and 'disabled'.
   *
   * @throws \InvalidArgumentException
   *   Thrown if the passed value for the $status variable is not an acceptable
   *   one.
   * @throws \Exception
   *   Thrown when the region or the button are not found or if the expected
   *   status does not match the actual one.
   *
   * @Given the :button button on the :label subscription card should be :status
   */
  public function assertSubscriptionButtonStatus(string $button, string $label, string $status): void {
    if (!in_array($status, ['enabled', 'disabled'])) {
      throw new \InvalidArgumentException('Allowed values for status variable are "enabled" and "disabled".');
    }

    $expected_status = $status === 'enabled';
    $card = $this->getGroupSubscriptionCardByHeading($label);
    $button = $this->findNamedElementInRegion($button, 'button', $card);
    $disabled = !$button->hasAttribute('disabled');
    Assert::assertEquals($expected_status, $disabled);
  }

  /**
   * Presses a button on a subscription card.
   *
   * @param string $button
   *   The button label.
   * @param string $label
   *   The group name.
   *
   * @throws \Exception
   *   Thrown when the card is not found.
   *
   * @Given I press :button on the :label subscription card
   */
  public function pressButtonOnSubscriptionCard(string $button, string $label): void {
    $card = $this->getGroupSubscriptionCardByHeading($label);
    $button = $card->findButton($button);
    $button->press();
  }

  /**
   * Asserts that a button exists in a subscription card.
   *
   * @param string $button
   *   The button label.
   * @param string $label
   *   The group name.
   *
   * @throws \Exception
   *   Thrown when the card or the button is not found.
   *
   * @Given I should see the :button button on the :collection subscription card
   */
  public function assertButtonExistsOnSubscriptionCard(string $button, string $label): void {
    $card = $this->getGroupSubscriptionCardByHeading($label);
    if (empty($card->findButton($button))) {
      throw new \Exception("The '$button' button was not found in the '$label' subscription card but should.");
    }
  }

  /**
   * Asserts that a button exists in a subscription card.
   *
   * @param string $button
   *   The button label.
   * @param string $label
   *   The group name.
   *
   * @throws \Exception
   *   Thrown when the card is not found or the button is.
   *
   * @Given I should not see the :button button on the :label subscription card
   */
  public function assertButtonNotExistsOnSubscriptionCard(string $button, string $label): void {
    $card = $this->getGroupSubscriptionCardByHeading($label);
    if ($card->findButton($button)) {
      throw new \Exception("The '$button' button was found in the '$label' subscription card but should not.");
    }
  }

  /**
   * Checks a material checkbox that represents a subscription's bundle.
   *
   * @param string $label
   *   The group name.
   * @param string $bundle
   *   The bundle to check.
   *
   * @throws \Exception
   *   Thrown when the card is not found or the checkbox is not styled properly.
   *
   * @Given I check the :bundle checkbox of the :label subscription
   */
  public function selectSubscriptionMaterialOptionInMySubscriptions(string $label, string $bundle): void {
    $label = $this->getGroupSubscriptionCardByHeading($label);
    $this->checkMaterialDesignField($bundle, $label);
  }

  /**
   * Unhecks a material checkbox that represents a subscription's bundle.
   *
   * @param string $label
   *   The group name.
   * @param string $bundle
   *   The bundle to check.
   *
   * @throws \Exception
   *   Thrown when the card is not found or there was an issue with the material
   *   checkbox.
   *
   * @Given I uncheck the :bundle checkbox of the :label subscription
   */
  public function deselectSubscriptionMaterialOptionInMySubscriptions(string $label, string $bundle): void {
    $label = $this->getGroupSubscriptionCardByHeading($label);
    $this->uncheckMaterialDesignField($bundle, $label);
  }

  /**
   * Selects group subscription options in the subscription settings form.
   *
   * This performs the action in the user interface, so the browser should be
   * navigated to the my subscriptions form before performing this step. This
   * will not submit the form.
   *
   * Table format:
   * | Group A | Discussion, Document, Event, News |
   * | Group B | Discussion, Event                 |
   *
   * @param \Behat\Gherkin\Node\TableNode $subscription_options
   *   The Behat table node containing the subscription options.
   * @param string $bundle
   *   The group bundle.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   Thrown when a checkbox for a given subscription option is not found.
   *
   * @When I select the following :bundle subscription options:
   */
  public function selectSubscriptionOptionsInMySubscriptions(TableNode $subscription_options, string $bundle): void {
    foreach ($subscription_options->getRowsHash() as $group_label => $bundle_ids) {
      $bundle_ids = $this->explodeCommaSeparatedStepArgument(strtolower($bundle_ids));
      $group = self::getRdfEntityByLabel($group_label, $bundle);
      foreach (CommunityContentHelper::BUNDLES as $bundle_id) {
        $locator = 'groups[' . $group->id() . '][bundles][' . $bundle_id . ']';
        if (in_array($bundle_id, $bundle_ids)) {
          if ($this->getSession()->getPage()->hasUncheckedField($locator)) {
            $this->getSession()->getPage()->checkField($locator);
          }
        }
        elseif ($this->getSession()->getPage()->hasCheckedField($locator)) {
          $this->getSession()->getPage()->uncheckField($locator);
        }
      }
    }
  }

  /**
   * Checks that the given subscription options are selected in the form.
   *
   * This performs the action in the user interface, so the browser should be
   * navigated to the my subscription form before performing this step.
   *
   * Table format:
   * | Group A | Discussion, Document, Event, News |
   * | Group B | Discussion, Event                 |
   *
   * @param \Behat\Gherkin\Node\TableNode $subscription_options
   *   The Behat table node containing the subscription options.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   Thrown when a checkbox for a given subscription option is not found or
   *   not in the expected state.
   *
   * @Then the following content subscriptions should be selected:
   */
  public function assertSubscriptionOptionsInSubscriptions(TableNode $subscription_options): void {
    foreach ($subscription_options->getRowsHash() as $group_label => $expected_bundle_ids) {
      $expected_bundle_ids = $this->explodeCommaSeparatedStepArgument(strtolower($expected_bundle_ids));
      $group = self::getRdfEntityByLabel($group_label);
      $subscription_bundles = JoinupSubscriptionsHelper::SUBSCRIPTION_BUNDLES[$group->bundle()];
      foreach ($subscription_bundles as $entity_type_id => $bundle_ids) {
        foreach ($bundle_ids as $bundle_id) {
          $key = implode('|', [$entity_type_id, $bundle_id]);
          $locator = 'groups[' . $group->id() . '][bundles][' . $key . ']';
          if (in_array($bundle_id, $expected_bundle_ids)) {
            $this->assertSession()->checkboxChecked($locator);
          }
          else {
            $this->assertSession()->checkboxNotChecked($locator);
          }
        }
      }
    }
  }

  /**
   * Checks that the given group content subscriptions are present.
   *
   * Table format:
   * | Group A | Discussion, Document, Event, News |
   * | Group B | Discussion, Event                 |
   *
   * @param \Behat\Gherkin\Node\TableNode $subscriptions
   *   The Behat table node containing the expected subscriptions. The first
   *   column contains the group labels, the second a comma-separated list
   *   of bundles the user is subscribed to.
   *
   * @throws \Exception
   *   Thrown when the user doesn't have a membership in one of the given
   *   groups. A membership is required in order to have subscriptions.
   *
   * @Then I should have the following content subscriptions:
   */
  public function assertGroupContentSubscriptions(TableNode $subscriptions): void {
    $user = $this->getUserManager()->getCurrentUser();
    /** @var \Drupal\Core\Session\AccountInterface $account */
    $account = User::load($user->uid);

    foreach ($subscriptions->getRowsHash() as $collection_label => $expected_bundle_ids) {
      $group = self::getRdfEntityByLabel($collection_label);
      $membership = $this->getMembershipByGroupAndUser($group, $account, OgMembershipInterface::ALL_STATES);
      $expected_bundle_ids = $this->explodeCommaSeparatedStepArgument(strtolower($expected_bundle_ids));

      $actual_bundle_ids = array_map(function (array $item): string {
        return $item['bundle'];
      }, $membership->get('subscription_bundles')->getValue());

      sort($expected_bundle_ids);
      sort($actual_bundle_ids);

      Assert::assertEquals($expected_bundle_ids, $actual_bundle_ids);
    }
  }

  /**
   * Checks that the current user is not subscribed to the given group.
   *
   * @param string $label
   *   The name of the group the user should not be subscribed to.
   * @param string $bundle
   *   The group bundle.
   *
   * @throws \Exception
   *   Thrown when the user is not a member of the given group.
   *
   * @Then I should not be subscribed to the :label :bundle
   */
  public function assertNoGroupContentSubscriptions(string $label, string $bundle): void {
    $user = $this->getUserManager()->getCurrentUser();
    $account = User::load($user->uid);
    $group = self::getRdfEntityByLabel($label, $bundle);
    $membership = $this->getMembershipByGroupAndUser($group, $account, OgMembershipInterface::ALL_STATES);
    Assert::assertEmpty($membership->get('subscription_bundles')->getValue());
  }

  /**
   * Checks that the digest for a user contains a certain message.
   *
   * This is based on \MessageDigestSubContext::assertDigestContains() and
   * adapted for the group content subscription digest.
   *
   * Example table:
   * @codingStandardsIgnoreStart
   *   | The content titled "My content" has been deleted       |
   *   | The "My content" page was deleted by an administrator. |
   * @codingStandardsIgnoreEnd
   *
   * @param \Behat\Gherkin\Node\TableNode $table
   *   A table containing the expected content of the different view modes for
   *   the message.
   * @param string $interval
   *   The digest interval, e.g. 'daily' or 'weekly'.
   * @param string $username
   *   The name of the user for which the message is intended.
   * @param string $scope
   *   The assertion scope. Can be one of the following values:
   *   - 'match': the expected messages should all be present. If there are
   *     any non-expected messages included in the digest the assertion fails.
   *   - 'include': the expected messages should be included in the digest, but
   *     the test does not fail if there are more messages present.
   * @param string|null $label
   *   Optional label of an entity that is related to the message.
   * @param string|null $entity_type
   *   Optional entity type of an entity that is related to the message.
   *
   * @Then the :interval group content subscription digest for :username should :scope the following message(s) for the :label :entity_type:
   * @Then the :interval group content subscription digest for :username should :scope the following message(s):
   */
  public function assertDigestContains(TableNode $table, string $interval, string $username, string $scope, $label = NULL, $entity_type = NULL): void {
    Assert::assertContains($scope, ['match', 'include'], sprintf('Unknown scope %s.', $scope));

    // Check that the notifier for the requested interval includes the view
    // mode that contains the message content.
    $notifier = $this->getMessageDigestNotifierForInterval($interval);
    $this->assertNotifierViewModes($notifier, ['mail_body']);

    $expected_messages = $table->getColumn(0);
    $user = user_load_by_name($username);
    $actual_messages = $this->getUserMessagesByNotifier($notifier, $user->id(), $entity_type, $label);
    if ($scope !== 'include') {
      $actual_message_labels = array_map(function (GroupContentSubscriptionMessageInterface $message): string {
        return $message->getSubscribedGroupContent()->label();
      }, $actual_messages);
      Assert::assertEquals(count($expected_messages), count($actual_messages), sprintf('Expected %d messages in the %s digest for user %s, found %d messages: %s.', count($expected_messages), $interval, $username, count($actual_messages), implode(', ', $actual_message_labels)));
    }

    $found_messages = [];
    foreach ($actual_messages as $actual_message) {
      $actual_message_content = $this->getRenderedMessage($actual_message, 'mail_body');
      foreach ($expected_messages as $expected_message) {
        if (strpos($actual_message_content, $expected_message) !== FALSE) {
          $found_messages[] = $expected_message;
          break 1;
        }
      }
    }

    $missing_messages = array_diff($expected_messages, $found_messages);
    if (!empty($missing_messages)) {
      $missing_messages = implode(', ', $missing_messages);
      $exception_message = !empty($entity_type) && !empty($label) ? "The expected messages '$missing_messages' for the '$label' $entity_type were not found in the $interval digest for $username." : "The expected messages '$missing_messages' were not found in the $interval digest for $username.";
      throw new \RuntimeException($exception_message);
    }
  }

  /**
   * Checks that the digest for a user does not contain a certain message.
   *
   * Example table:
   * @codingStandardsIgnoreStart
   *   | mail_subject | The content titled "My content" has been deleted       |
   *   | mail_body    | The "My content" page was deleted by an administrator. |
   * @codingStandardsIgnoreEnd
   *
   * @param \Behat\Gherkin\Node\TableNode $table
   *   A table containing the expected content of the different view modes for
   *   the message that should not be present.
   * @param string $interval
   *   The digest interval, e.g. 'daily' or 'weekly'.
   * @param string $username
   *   The name of the user for which the message is intended.
   * @param string|null $label
   *   Optional label of an entity that is related to the message.
   * @param string|null $entity_type
   *   Optional entity type of an entity that is related to the message.
   *
   * @Then the :interval group content subscription digest for :username should not contain the following message(s) for the :label :entity_type:
   * @Then the :interval group content subscription digest for :username should not contain the following message(s):
   */
  public function assertDigestNotContains(TableNode $table, string $interval, string $username, $label = NULL, $entity_type = NULL): void {
    // Check that the notifier for the requested interval includes the view
    // mode that contains the message content.
    $notifier = $this->getMessageDigestNotifierForInterval($interval);
    $this->assertNotifierViewModes($notifier, ['mail_body']);

    $expected_messages = $table->getColumn(0);

    $user = user_load_by_name($username);
    $actual_messages = $this->getUserMessagesByNotifier($notifier, $user->id(), $entity_type, $label);

    $found_messages = [];
    foreach ($actual_messages as $actual_message) {
      $actual_message_content = $this->getRenderedMessage($actual_message, 'mail_body');
      foreach ($expected_messages as $expected_message) {
        if (strpos($actual_message_content, $expected_message) !== FALSE) {
          $found_messages[] = $expected_message;
          break 1;
        }
      }
    }

    if (!empty($found_messages)) {
      $found_messages = implode(', ', $found_messages);
      $exception_message = !empty($entity_type) && !empty($label) ? "The expected messages '$found_messages' for the '$label' $entity_type were not expected to be found in the $interval digest for $username." : "The expected messages '$found_messages' were not expected to be found in the $interval digest for $username.";
      throw new \RuntimeException($exception_message);
    }
  }

  /**
   * Returns the Joinup subscription service.
   *
   * @return \Drupal\joinup_subscription\JoinupDiscussionSubscriptionInterface
   *   The subscription service.
   */
  protected function getDiscussionSubscriptionService(): JoinupDiscussionSubscriptionInterface {
    return \Drupal::service('joinup_subscription.discussion_subscription');
  }

  /**
   * Finds a card element by its heading.
   *
   * @param string $heading
   *   The heading of the card to find.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The element found.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   Thrown when the element is not found.
   */
  protected function getGroupSubscriptionCardByHeading(string $heading): NodeElement {
    return $this->getListingByHeading('group-subscription', $heading);
  }

  /**
   * Checks that the digest mail sent to the given user contains the right data.
   *
   * This will check that there is exactly 1 digest mail in the collector for
   * the given user, and that the mail will contain the expected sections (both
   * collection headers and community content) in the expected order.
   *
   * Table format:
   * | title   |
   * | Arctic  |
   * | Sea ice |
   *
   * @param string $username
   *   The name of the user to whom the digest mail is sent.
   * @param \Behat\Gherkin\Node\TableNode $table
   *   The table that contains the expected sections, in the expected order.
   *
   * @throws \Exception
   *   Throws an exception when a parameter is not the expected one.
   *
   * @Then the group content subscription digest sent to :username contains the following sections:
   */
  public function assertGroupContentSubscriptionEmailSections(string $username, TableNode $table): void {
    // Remove the table header from the array.
    $expected_sections = $table->getColumn(0);
    array_shift($expected_sections);

    $user = user_load_by_name($username);
    $email_address = $user->getEmail();
    $emails = $this->getGroupSubscriptionEmailsByEmail($email_address);

    Assert::assertCount(1, $emails, "Expected 1 digest message for user $username, found " . count($emails) . ' messages.');

    $email = reset($emails);

    // Since this is a HTML formatted email it is difficult to parse. We will
    // split the body text in lines, and check every line until we find one that
    // matches the expected section, discarding all non-matching lines. This way
    // we can ascertain that every section is in the right order.
    $email_lines = explode("\n", (string) $email['body']);
    foreach ($expected_sections as $expected_section) {
      do {
        $line = array_shift($email_lines);
        // If we reach the end before finding the section, the section does not
        // exist or is not in the correct order.
        if (empty($email_lines)) {
          throw new \Exception("The digest message for user $username does not contain '$expected_section' or is not in the correct order.");
        }
      } while (strpos($line, $expected_section) === FALSE);
    }
  }

  /**
   * Checks that the digest mail sent to the given user has the right title.
   *
   * This will check that there is exactly 1 digest mail in the collector for
   * the given user, and that the mail has the expected title. The title follows
   * a predefined pattern so we could automate this check without requiring a
   * separate step definition but this is provided for the benefit of the
   * business stakeholders who can validate that their chosen format is present.
   *
   * @param string $username
   *   The name of the user to whom the digest mail is sent.
   * @param string $subject
   *   The expected subject for the digest mail.
   *
   * @throws \Exception
   *   Throws an exception when the user doesn't exist, has no digest message or
   *   the message subject is incorrect.
   *
   * @Then the content subscription digest sent to :username should have the subject :subject
   */
  public function assertGroupContentSubscriptionEmailSubject(string $username, string $subject): void {
    $user = user_load_by_name($username);
    $email_address = $user->getEmail();
    $emails = $this->getGroupSubscriptionEmailsByEmail($email_address);

    Assert::assertCount(1, $emails, "Expected 1 digest message for user $username, found " . count($emails) . ' messages.');

    $email = reset($emails);
    Assert::assertEquals($subject, $email['subject']);
  }

  /**
   * Returns the sent group subscription digest messages for the user.
   *
   * @param string $email_address
   *   The email of the recipient.
   *
   * @return array
   *   An array of emails found.
   *
   * @throws \Exception
   *   Thrown if no emails are found or no user exists with the given data.
   */
  protected function getGroupSubscriptionEmailsByEmail(string $email_address): array {
    $emails = [];

    foreach (self::MESSAGE_INTERVALS as $interval) {
      $emails = array_merge($emails, $this->getEmailsBySubjectAndMail("Joinup: $interval digest message", $email_address, FALSE));
    }

    return $emails;
  }

}
