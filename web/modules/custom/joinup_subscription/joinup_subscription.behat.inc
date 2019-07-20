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
use Drupal\user\Entity\User;
use PHPUnit\Framework\Assert;

/**
 * Behat step definitions for testing subscriptions.
 */
class SubscriptionSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use BrowserCapabilityDetectionTrait;
  use EntityTrait;
  use MaterialDesignTrait;
  use OgTrait;
  use RdfEntityTrait;
  use UserTrait;
  use UtilityTrait;
  use TraversingTrait;

  /**
   * Navigates to the subscription settings form of the given user.
   *
   * @param string $username
   *   The name of the user.
   *
   * @When I go to the subscription settings form of :username
   */
  public function visitSubscriptionSettingsForm(string $username): void {
    $user = $this->getUserByName($username);
    $url = Url::fromRoute('joinup_subscription.subscription_settings', [
      'user' => $user->id(),
    ]);
    $this->visitPath($url->toString());
  }

  /**
   * Navigates to the subscription dashboard of the given user.
   *
   * @param string $username
   *   The name of the user.
   *
   * @When I go to the subscription dashboard of :username
   * @When I go to my subscription dashboard
   */
  public function visitSubscriptionDashboard(string $username = NULL): void {
    if (!empty($username)) {
      $user = $this->getUserByName($username);
      $url = Url::fromRoute('joinup_subscription.subscriptions', [
        'user' => $user->id(),
      ]);
    }
    else {
      $url = Url::fromRoute('joinup_subscription.subscriptions_page');
    }
    $this->visitPath($url->toString());
  }

  /**
   * Subscribes the given users to the given entity.
   *
   * @param \Behat\Gherkin\Node\TableNode $table
   *   A table with the keys 'username' and 'title'.
   * @param string $bundle
   *   The entity bundle to which the entity with given title corresponds.
   * @param string $entity_type
   *   The entity type to which the entity with given title corresponds.
   *
   * @Given (the following ):bundle :entity_type subscriptions:
   */
  public function subscribe(TableNode $table, string $bundle, string $entity_type): void {
    $subscription_service = $this->getSubscriptionService();
    $entity_type = self::translateEntityTypeAlias($entity_type);
    foreach ($table->getColumnsHash() as $values) {
      $user = $this->getUserByName($values['username']);
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $this->getEntityByLabel($entity_type, $values['title'], $bundle);
      // @todo Currently we only have subscriptions for discussions. Provide a
      //   lookup table for the discussion ID once we have more.
      $subscription_service->subscribe($user, $entity, 'subscribe_discussions');
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
   * @Given the :button button on the :collection subscription card should be :status
   */
  public function assertSubscriptionButtonStatus(string $button, string $collection, string $status): void {
    if (!in_array($status, ['enabled', 'disabled'])) {
      throw new InvalidArgumentException('Allowed values for status variable are "enabled" and "disabled".');
    }

    $expected_status = $status === 'enabled';
    $card = $this->getCollectionSubscriptionCardByHeading($collection);
    $button = $card->findButton($button);
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
   * @Given I check the :bundle checkbox of the :collection subscription
   */
  public function selectSubscriptionMaterialOptionInDashboard(string $collection, string $bundle): void {
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
   * @Given I uncheck the :bundle checkbox of the :collection subscription
   */
  public function deselectSubscriptionMaterialOptionInDashboard(string $collection, string $bundle): void {
    $collection = $this->getCollectionSubscriptionCardByHeading($collection);
    $this->uncheckMaterialDesignField($bundle, $collection);
  }

  /**
   * Selects collection subscription options in the subscription dashboard.
   *
   * This performs the action in the user interface, so the browser should be
   * navigated to the subscription dashboard before performing this step. This
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
  public function selectSubscriptionOptionsInDashboard(TableNode $subscription_options): void {
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
   * Checks that the given subscription options are selected in the dashboard.
   *
   * This performs the action in the user interface, so the browser should be
   * navigated to the subscription dashboard before performing this step.
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
  public function assertSubscriptionOptionsInDashboard(TableNode $subscription_options): void {
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
      $membership = $this->getMembershipByGroupAndUser($collection, $account);
      $actual_bundle_ids = array_map(function (array $item): string {
        return $item['bundle'];
      }, $membership->get('subscription_bundles')->getValue());

      sort($expected_bundle_ids);
      sort($actual_bundle_ids);
      Assert::assertEquals($expected_bundle_ids, $actual_bundle_ids);
    }
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
