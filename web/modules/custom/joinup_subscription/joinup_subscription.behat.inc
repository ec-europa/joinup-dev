<?php

/**
 * @file
 * Contains \SubscriptionSubContext.
 */

declare(strict_types = 1);

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Drupal\Core\Url;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\BrowserCapabilityDetectionTrait;
use Drupal\joinup\Traits\EntityTrait;
use Drupal\joinup\Traits\MaterialDesignTrait;
use Drupal\joinup\Traits\OgTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\joinup\Traits\TraversingTrait;
use Drupal\joinup\Traits\UserTrait;
use Drupal\joinup\Traits\UtilityTrait;
use Drupal\joinup_community_content\CommunityContentHelper;
use Drupal\joinup_subscription\JoinupSubscriptionInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Assert;

/**
 * Behat step definitions for testing subscriptions.
 */
class JoinupSubscriptionSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use BrowserCapabilityDetectionTrait;
  use EntityTrait;
  use MaterialDesignTrait;
  use OgTrait;
  use RdfEntityTrait;
  use UserTrait;
  use UtilityTrait;
  use TraversingTrait;

  /**
   * Navigates to the My subscriptions form of the given user.
   *
   * @param string $username
   *   The name of the user.
   *
   * @When I go to the subscription settings of :username
   * @When I go to my subscriptions
   */
  public function visitMySubscriptions(string $username = NULL): void {
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
   * Subscribes the given users to the given discussion.
   *
   * @param \Behat\Gherkin\Node\TableNode $table
   *   A table with the keys 'username' and 'title'.
   *
   * @Given (the following )discussion subscriptions:
   */
  public function subscribeToDiscussion(TableNode $table): void {
    $subscription_service = $this->getSubscriptionService();
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
   * Subscribes the given users to the given collection content bundles.
   *
   * Table format:
   * | collection   | user   | subscriptions                     |
   * | Collection A | user A | discussion, document, event, news |
   * | Collection B | user B | discussion, event                 |
   *
   * @param \Behat\Gherkin\Node\TableNode $subscription_table
   *   A table with the data for subscribing the users.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when a membership cannot be updated with the new subscriptions.
   * @throws \Exception
   *   Thrown if a membership is not found for a given user in a given
   *   collection.
   *
   * @Given (the following )collection content subscriptions:
   */
  public function subscribeToCollectionContent(TableNode $subscription_table): void {
    foreach ($subscription_table->getColumnsHash() as $values) {
      $collection = $this->getRdfEntityByLabel($values['collection'], 'collection');
      $user = $this->getUserByName($values['user']);
      $membership = $this->getMembershipByGroupAndUser($collection, $user, OgMembershipInterface::ALL_STATES);
      $subscriptions = [];
      foreach ($this->explodeCommaSeparatedStepArgument(strtolower($values['subscriptions'])) as $bundle) {
        Assert::assertTrue(in_array($bundle, CommunityContentHelper::BUNDLES), "Unknown bundle $bundle.");
        $subscriptions[] = [
          'entity_type' => 'node',
          'bundle' => $bundle,
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
   * @param string $collection
   *   The collection name.
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
   * @Given the :button button on the :collection subscription card should be :status
   */
  public function assertSubscriptionButtonStatus(string $button, string $collection, string $status): void {
    if (!in_array($status, ['enabled', 'disabled'])) {
      throw new InvalidArgumentException('Allowed values for status variable are "enabled" and "disabled".');
    }

    $expected_status = $status === 'enabled';
    $card = $this->getCollectionSubscriptionCardByHeading($collection);
    $button = $this->findNamedElementInRegion($button, 'button', $card);
    $disabled = !($button->getAttribute('disabled') === 'disabled');
    Assert::assertEquals($expected_status, $disabled);
  }

  /**
   * Presses a button on a subscription card.
   *
   * @param string $button
   *   The button label.
   * @param string $collection
   *   The collection name.
   *
   * @throws \Exception
   *   Thrown when the card is not found.
   *
   * @Given I press :button on the :collection subscription card
   */
  public function pressButtonOnSubscriptionCard(string $button, string $collection): void {
    $card = $this->getCollectionSubscriptionCardByHeading($collection);
    $button = $card->findButton($button);
    $button->press();
  }

  /**
   * Asserts that a button exists in a subscription card.
   *
   * @param string $button
   *   The button label.
   * @param string $collection
   *   The collection name.
   *
   * @throws \Exception
   *   Thrown when the card or the button is not found.
   *
   * @Given I should see the :button button on the :collection subscription card
   */
  public function assertButtonExistsOnSubscriptionCard(string $button, string $collection): void {
    $card = $this->getCollectionSubscriptionCardByHeading($collection);
    if (empty($card->findButton($button))) {
      throw new Exception("The '$button' button was not found in the '$collection' subscription card but should.");
    }
  }

  /**
   * Asserts that a button exists in a subscription card.
   *
   * @param string $button
   *   The button label.
   * @param string $collection
   *   The collection name.
   *
   * @throws \Exception
   *   Thrown when the card is not found or the button is.
   *
   * @Given I should not see the :button button on the :collection subscription card
   */
  public function assertButtonNotExistsOnSubscriptionCard(string $button, string $collection): void {
    $card = $this->getCollectionSubscriptionCardByHeading($collection);
    if ($card->findButton($button)) {
      throw new Exception("The '$button' button was found in the '$collection' subscription card but should not.");
    }
  }

  /**
   * Checks a material checkbox that represents a subscription's bundle.
   *
   * @param string $collection
   *   The collection name.
   * @param string $bundle
   *   The bundle to check.
   *
   * @throws \Exception
   *   Thrown when the card is not found or the checkbox is not styled properly.
   *
   * @Given I check the :bundle checkbox of the :collection subscription
   */
  public function selectSubscriptionMaterialOptionInMySubscriptions(string $collection, string $bundle): void {
    $collection = $this->getCollectionSubscriptionCardByHeading($collection);
    $this->checkMaterialDesignField($bundle, $collection);
  }

  /**
   * Unhecks a material checkbox that represents a subscription's bundle.
   *
   * @param string $collection
   *   The collection name.
   * @param string $bundle
   *   The bundle to check.
   *
   * @throws \Exception
   *   Thrown when the card is not found or there was an issue with the material
   *   checkbox.
   *
   * @Given I uncheck the :bundle checkbox of the :collection subscription
   */
  public function deselectSubscriptionMaterialOptionInMySubscriptions(string $collection, string $bundle): void {
    $collection = $this->getCollectionSubscriptionCardByHeading($collection);
    $this->uncheckMaterialDesignField($bundle, $collection);
  }

  /**
   * Selects collection subscription options in the subscription settings form.
   *
   * This performs the action in the user interface, so the browser should be
   * navigated to the my subscriptions form before performing this step. This
   * will not submit the form.
   *
   * Table format:
   * | Collection A | Discussion, Document, Event, News |
   * | Collection B | Discussion, Event                 |
   *
   * @param \Behat\Gherkin\Node\TableNode $subscription_options
   *   The Behat table node containing the subscription options.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   Thrown when a checkbox for a given subscription option is not found.
   *
   * @When I select the following collection subscription options:
   */
  public function selectSubscriptionOptionsInMySubscriptions(TableNode $subscription_options): void {
    foreach ($subscription_options->getRowsHash() as $collection_label => $bundle_ids) {
      $bundle_ids = $this->explodeCommaSeparatedStepArgument(strtolower($bundle_ids));
      $collection = self::getRdfEntityByLabel($collection_label, 'collection');
      foreach (CommunityContentHelper::BUNDLES as $bundle_id) {
        $locator = 'collections[' . $collection->id() . '][bundles][' . $bundle_id . ']';
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
   * | Collection A | Discussion, Document, Event, News |
   * | Collection B | Discussion, Event                 |
   *
   * @param \Behat\Gherkin\Node\TableNode $subscription_options
   *   The Behat table node containing the subscription options.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   Thrown when a checkbox for a given subscription option is not found or
   *   not in the expected state.
   *
   * @Then the following collection content subscriptions should be selected:
   */
  public function assertSubscriptionOptionsInMySubscriptions(TableNode $subscription_options): void {
    foreach ($subscription_options->getRowsHash() as $collection_label => $bundle_ids) {
      $bundle_ids = $this->explodeCommaSeparatedStepArgument(strtolower($bundle_ids));
      $collection = self::getRdfEntityByLabel($collection_label, 'collection');
      foreach (CommunityContentHelper::BUNDLES as $bundle_id) {
        $locator = 'collections[' . $collection->id() . '][bundles][' . $bundle_id . ']';
        if (in_array($bundle_id, $bundle_ids)) {
          $this->assertSession()->checkboxChecked($locator);
        }
        else {
          $this->assertSession()->checkboxNotChecked($locator);
        }
      }
    }
  }

  /**
   * Checks that the given collection content subscriptions are present.
   *
   * Table format:
   * | Collection A | Discussion, Document, Event, News |
   * | Collection B | Discussion, Event                 |
   *
   * @param \Behat\Gherkin\Node\TableNode $subscriptions
   *   The Behat table node containing the expected subscriptions. The first
   *   column contains the collection labels, the second a comma-separated list
   *   of bundles the user is subscribed to.
   *
   * @throws \Exception
   *   Thrown when the user doesn't have a membership in one of the given
   *   collections. A membership is required in order to have subscriptions.
   *
   * @Then I should have the following collection content subscriptions:
   */
  public function assertCollectionContentSubscriptions(TableNode $subscriptions): void {
    $user = $this->getUserManager()->getCurrentUser();
    $account = User::load($user->uid);
    foreach ($subscriptions->getRowsHash() as $collection_label => $expected_bundle_ids) {
      $collection = self::getRdfEntityByLabel($collection_label);
      $expected_bundle_ids = $this->explodeCommaSeparatedStepArgument(strtolower($expected_bundle_ids));
      $membership = $this->getMembershipByGroupAndUser($collection, $account, OgMembershipInterface::ALL_STATES);
      $actual_bundle_ids = array_map(function (array $item): string {
        return $item['bundle'];
      }, $membership->get('subscription_bundles')->getValue());

      sort($expected_bundle_ids);
      sort($actual_bundle_ids);
      Assert::assertEquals($expected_bundle_ids, $actual_bundle_ids);
    }
  }

  /**
   * Checks that the current user is not subscribed to the given collection.
   *
   * @param string $label
   *   The name of the collection the user should not be subscribed to.
   *
   * @throws \Exception
   *   Thrown when the user is not a member of the given collection.
   *
   * @Then I should not be subscribed to the :label collection
   */
  public function assertNoCollectionContentSubscriptions(string $label): void {
    $user = $this->getUserManager()->getCurrentUser();
    $account = User::load($user->uid);
    $collection = self::getRdfEntityByLabel($label);
    $membership = $this->getMembershipByGroupAndUser($collection, $account, OgMembershipInterface::ALL_STATES);
    Assert::assertEmpty($membership->get('subscription_bundles')->getValue());
  }

  /**
   * Returns the Joinup subscription service.
   *
   * @return \Drupal\joinup_subscription\JoinupSubscriptionInterface
   *   The subscription service.
   */
  protected function getSubscriptionService(): JoinupSubscriptionInterface {
    return \Drupal::service('joinup_subscription.subscription');
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
  protected function getCollectionSubscriptionCardByHeading(string $heading): NodeElement {
    return $this->getListingByHeading('collection-subscription', $heading);
  }

}
