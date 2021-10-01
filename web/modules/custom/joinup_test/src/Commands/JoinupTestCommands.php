<?php

declare(strict_types = 1);

namespace Drupal\joinup_test\Commands;

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
   * Sets up success, warning notification messages etc, in the homepage.
   *
   * @param string $message
   *   (optional) The message to show in all messages.
   *
   * @usage joinup_test:setup_messages "This is some random text."
   *   Show the message "This is some random text." in various formats.
   *
   * @command joinup_test:setup_messages
   * @aliases jt-sm
   */
  public function setupTestMessages(string $message = 'Some random text message.'): void {
    if (empty(trim($message))) {
      $this->logger->error('The message parameter cannot be empty.');
    }

    $state = $this->stateManager->get('joinup_test_messages');
    if (!empty($state)) {
      $this->logger->notice('Previous messages have been found and will be cleared now.');
      $this->clearTestMessages();
    }

    foreach (SiteAlert::SEVERITY_OPTIONS as $severity_key => $severity_value) {
      $site_alert = SiteAlert::create([
        'active' => 1,
        'severity' => $severity_key,
        'label' => $this->t('Joinup test :severity', [
          ':severity' => $severity_value,
        ]),
        'message' => "$severity_value severity site alert: {$message}",
      ]);
      $site_alert->save();
      $state['site_alerts'][] = $site_alert->id();
    }
    // This will be used to display the messenger messages.
    $state['message'] = $message;

    $this->stateManager->set('joinup_test_messages', $state);
    $this->logger()->success("The message '{$message}' will be shown in various format in the homepage.");
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
      // Avoid erros if the site alert has been deleted in a different way.
      if ($site_alert = SiteAlert::load($alert_id)) {
        $site_alert->delete();
      }
    }

    $this->stateManager->delete('joinup_test_messages');
    $this->logger()->success('All messages have been cleared.');
  }

}
