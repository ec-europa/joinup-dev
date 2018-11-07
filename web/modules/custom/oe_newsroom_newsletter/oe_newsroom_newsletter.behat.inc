<?php

/**
 * @file
 * Contains \OpenEuropaNewsroomNewsletterSubContext.
 */

declare(strict_types = 1);

use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\ScenarioTagTrait;
use Drupal\joinup\Traits\ConfigReadOnlyTrait;
use Drupal\oe_newsroom_newsletter\NewsletterSubscriber\MockNewsletterSubscriber;
use PHPUnit\Framework\Assert;

/**
 * Behat integration for the OpenEuropa Newsroom Newsletter module.
 */
class OpenEuropaNewsroomNewsletterSubContext extends DrupalSubContextBase {

  use ConfigReadOnlyTrait;
  use ScenarioTagTrait;

  /**
   * The newsletter subscriber that was originally configured.
   *
   * If tests are being run using the `@newsroom_newsletter` tag then we will
   * temporarily use a mocked newsletter subscriber that keeps track of the
   * subscriptions made during the test. This contains the subscriber that was
   * originally configured so we can restore it after the test ends.
   *
   * @var string|null
   */
  protected $originalNewsletterSubscriber;

  /**
   * Clears the newsletter subscriptions that are being tracked.
   *
   * @Given there are no newsletter subscribers
   */
  public function clearNewsletterSubscriptions() {
    // @todo Uncomment this line when TagTrait is available in DrupalExtension.
    // @see https://github.com/jhedstrom/drupalextension/pull/510
    // $this->assertNewsletterTagPresent();
    \Drupal::state()->set(MockNewsletterSubscriber::STATE_KEY, []);
    \Drupal::state()->resetCache();
  }

  /**
   * Checks if the current scenario or feature has the required tag.
   *
   * Call this in steps that use the mocked newsletter subscriber so that the
   * developer is alerted if this tag is forgotten to be added to the scenario.
   */
  protected function assertNewsletterTagPresent(): void {
    // @todo Change this to use TagTrait once ScenarioTagTrait is deprecated.
    // @see https://github.com/jhedstrom/drupalextension/pull/510
    $tags = $this->getScenario()->getTags();
    Assert::assertTrue(in_array('newsroom_newsletter', $tags));
  }

  /**
   * Enables the mock newsletter subscription service.
   *
   * @BeforeScenario @newsroom_newsletter
   */
  public function beforeNewsletterScenario(): void {
    // Check if the user is overriding the newsletter subscription service in
    // settings.php, and abort the test if this is the case. We won't be able to
    // enable the mocked subscription service if it is overridden, and the test
    // result will not be reliable.
    $config = \Drupal::configFactory()->get('oe_newsroom_newsletter.subscriber');
    $config_is_overridden = $config->hasOverrides('class');
    $mock_subscriber_is_used = $config->get('class') === MockNewsletterSubscriber::class;
    if ($config_is_overridden && !$mock_subscriber_is_used) {
      throw new \Exception('Cannot inspect newsletter subscriptions since "oe_newsroom_newsletter.subscriber" is overridden in settings.php.');
    }
    // Set up the mock subscriber. This is only needed if we aren't already
    // using it.
    if (!$mock_subscriber_is_used) {
      self::bypassReadOnlyConfig();

      $config = \Drupal::configFactory()->getEditable('oe_newsroom_newsletter.subscriber');
      $this->originalNewsletterSubscriber = $config->get('class');
      $config->set('class', MockNewsletterSubscriber::class)->save();

      self::restoreReadOnlyConfig();
    }

    // Clear out any subscriptions that might be left behind from previous
    // scenarios.
    $this->clearNewsletterSubscriptions();
  }

  /**
   * Disables the mock newsletter subscription service.
   *
   * @AfterScenario @newsroom_newsletter
   */
  public function afterNewsletterScenario(): void {
    $config = \Drupal::configFactory()->get('oe_newsroom_newsletter.subscriber');
    if ($config->get('class') !== $this->originalNewsletterSubscriber) {
      // Temporarily bypass read only config so that we can disable the mock
      // service.
      self::bypassReadOnlyConfig();

      $config = \Drupal::configFactory()->getEditable('oe_newsroom_newsletter.subscriber');
      $config->set('class', $this->originalNewsletterSubscriber)->save();

      self::restoreReadOnlyConfig();
    }
  }

}
