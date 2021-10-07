<?php

declare(strict_types = 1);

namespace Drupal\joinup_test\Commands;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\site_alert\Entity\SiteAlert;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class JoinupTestCommands extends DrushCommands {

  use StringTranslationTrait;

  /**
   * A set of site alerts to show.
   */
  protected const DEFAULT_ALERTS = [
    'low' => 'Joinup has a new look! <a href="#">Let us know</a> what you think.',
    'medium' => 'We are migrating to EU Login. Please <a href="#">update your account</a> at your earliest convenience.',
    'high' => 'Scheduled maintenance is taking place at 17h CET. It will require a downtime of 15 minutes. <a href="#">What\'s new</a>',
  ];

  /**
   * A set of status messages to show.
   */
  protected const DEFAULT_MESSAGES = [
    MessengerInterface::TYPE_STATUS => [
      'You are signed in successfully.',
      'Your Joinup subscription has been activated. <a href="#">Manage your subscriptions</a>',
    ],
    MessengerInterface::TYPE_WARNING => [
      'We are migrating to EU Login and your account needs to be linked. <a href="#">Learn more</a>',
    ],
    MessengerInterface::TYPE_ERROR => [
      'Your message could not be delivered. Contact <a href="#">Joinup tech support</a> for assistance.',
    ],
  ];

  /**
   * The state manager service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $stateManager;

  /**
   * Constructs a JoinupTestCommands object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state manager service.
   */
  public function __construct(StateInterface $state) {
    parent::__construct();
    $this->stateManager = $state;
  }

  /**
   * Sets up success, warning, notification messages and site alerts.
   *
   * @usage joinup_test:setup_messages
   *   Shows all status messages and site alerts.
   *
   * @command joinup_test:setup_messages
   * @aliases jt-sm
   */
  public function setupTestMessages(string $message = 'Some random text message.'): void {
    $state = $this->stateManager->get('joinup_test_messages');
    if (!empty($state)) {
      $this->logger->notice('Previous messages have been found and will be cleared now.');
      $this->clearTestMessages();
    }

    foreach (self::DEFAULT_ALERTS as $severity => $message) {
      $site_alert = SiteAlert::create([
        'active' => 1,
        'severity' => $severity,
        'label' => $severity,
        'message' => $message,
      ]);
      $site_alert->save();
      $state['site_alerts'][] = $site_alert->id();
    }
    // This will be used to display the messenger messages.
    $state['messages'] = self::DEFAULT_MESSAGES;

    $this->stateManager->set('joinup_test_messages', $state);
    $this->logger()->success("The messages will be be shown in the website.");
  }

  /**
   * Clears up the test messages from the homepage.
   *
   * @usage joinup_test:clear_messages
   *   Removes all site alerts and stops showing test messages.
   *
   * @command joinup_test:clear_messages
   * @aliases jt-cm
   */
  public function clearTestMessages(string $message = 'Some random text message.'): void {
    $state = $this->stateManager->get('joinup_test_messages');

    if (empty($state)) {
      // Restore the state first before setting up the new messages.
      $this->logger()->info('The test messages do not seem to be enabled. Ignoring.');
      return;
    }

    foreach ($state['site_alerts'] as $alert_id) {
      // Avoid errors if the site alert has been deleted in a different way.
      if ($site_alert = SiteAlert::load($alert_id)) {
        $site_alert->delete();
      }
    }

    $this->stateManager->delete('joinup_test_messages');
    $this->logger()->success('All messages have been cleared.');
  }

}
