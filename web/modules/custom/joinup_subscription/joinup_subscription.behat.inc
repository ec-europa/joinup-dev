<?php

/**
 * @file
 * Contains \SubscriptionSubContext.
 */

declare(strict_types = 1);

use Behat\Gherkin\Node\TableNode;
use Drupal\Core\Url;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\EntityTrait;
use Drupal\joinup\Traits\OgTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\joinup\Traits\UserTrait;
use Drupal\joinup\Traits\UtilityTrait;
use Drupal\joinup_subscription\JoinupSubscriptionInterface;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Assert;

/**
 * Behat step definitions for testing subscriptions.
 */
class SubscriptionSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use EntityTrait;
  use OgTrait;
  use RdfEntityTrait;
  use UserTrait;
  use UtilityTrait;

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
   * Selects collection subscription options in the subscription dashboard.
   *
   * This performs the action in the user interface, so the browser should be
   * navigated to the subscription dashboard before performing this step. This
   * will not submit the form.
   *
   * Table format:
   * | Discussion | all|none |
   * | Document   | all|none |
   * | Event      | all|none |
   * | News       | all|none |
   *
   * @param \Behat\Gherkin\Node\TableNode $subscription_options
   *   The Behat table node containing the subscription options.
   * @param string $collection_label
   *   The collection for which the subscription options will be selected.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   Thrown when the dropdown for the given collection and bundle is not
   *   found.
   *
   * @When I select the following subscription options for the :collection collection:
   */
  public function selectSubscriptionOptionsInDashboard(TableNode $subscription_options, string $collection_label): void {
    $collection = self::getRdfEntityByLabel($collection_label, 'collection');
    foreach ($subscription_options->getRowsHash() as $bundle_id => $subscription_option) {
      $bundle_id = strtolower($bundle_id);
      $locator = 'collections[' . $collection->id() . '][bundles][' . $bundle_id . ']';
      $this->getSession()->getPage()->selectFieldOption($locator, $subscription_option);
    }
  }

  /**
   * Checks that the given collection content subscriptions are present.
   *
   * Table format:
   * | Collection A | discussion, document, event, news |
   * | Collection B | discussion, event                 |
   *
   * @param \Behat\Gherkin\Node\TableNode $subscriptions
   *   The Behat table node containing the expected subscriptions. The first
   *   column contains the collection labels, the second a comma-separated list
   *   of bundles the user is subscribed to.
   *
   * @Then I should have the following collection content subscriptions:
   */
  public function assertCollectionContentSubscriptions(TableNode $subscriptions) {
    $user = $this->getUserManager()->getCurrentUser();
    $account = User::load($user->uid);
    foreach ($subscriptions->getRowsHash() as $collection_label => $expected_bundle_ids) {
      $collection = self::getRdfEntityByLabel($collection_label);
      $expected_bundle_ids = $this->explodeCommaSeparatedStepArgument($expected_bundle_ids);
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

}
