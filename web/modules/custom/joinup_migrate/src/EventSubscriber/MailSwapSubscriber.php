<?php

namespace Drupal\joinup_migrate\EventSubscriber;

use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Event\MigrateRollbackEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A subscriber that swaps out the system mail during migration.
 */
class MailSwapSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      MigrateEvents::PRE_IMPORT => 'importToggleMailOff',
      MigrateEvents::POST_IMPORT => 'importToggleMailOn',
      MigrateEvents::PRE_ROLLBACK => 'rollbackToggleMailOff',
      MigrateEvents::POST_ROLLBACK => 'rollbackToggleMailOn',
    ];
  }

  /**
   * Switches mailing off on import.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The event object.
   */
  public function importToggleMailOff(MigrateImportEvent $event) {
    $this->toggleMailOff();
  }

  /**
   * Switches mailing off on rollback.
   *
   * @param \Drupal\migrate\Event\MigrateRollbackEvent $event
   *   The event object.
   */
  public function rollbackToggleMailOff(MigrateRollbackEvent $event) {
    $this->toggleMailOff();
  }

  /**
   * Restores mailing after import.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The event object.
   */
  public function importToggleMailOn(MigrateImportEvent $event) {
    $this->toggleMailOn();
  }

  /**
   * Restores mailing after rollback.
   *
   * @param \Drupal\migrate\Event\MigrateRollbackEvent $event
   *   The event object.
   */
  public function rollbackToggleMailOn(MigrateRollbackEvent $event) {
    $this->toggleMailOn();
  }

  /**
   * Switches mailing off.
   */
  protected function toggleMailOff() {
    $config_factory = \Drupal::configFactory();
    $system_mail = $config_factory->getEditable('system.mail');
    $mailsystem = $config_factory->getEditable('mailsystem.settings');

    // Save the current mailer.
    \Drupal::state()->set('joinup_migrate.mail', [
      'system' => $system_mail->get('interface.default'),
      'mailsystem' => $mailsystem->get('defaults.sender'),
    ]);

    // Switch to 'null' mailer.
    $system_mail->set('interface.default', 'null')->save();
    $mailsystem->set('defaults.sender', 'null')->save();
  }

  /**
   * Switches mailing back on.
   */
  protected function toggleMailOn() {
    $config_factory = \Drupal::configFactory();
    $system_mail = $config_factory->getEditable('system.mail');
    $mailsystem = $config_factory->getEditable('mailsystem.settings');

    // Get the system mailer.
    $state = \Drupal::state();
    $mail = $state->get('joinup_migrate.mail');
    $state->delete('joinup_migrate.mail');

    // Restore the system mailer.
    $system_mail->set('interface.default', $mail['system'])->save();
    $mailsystem->set('defaults.sender', $mail['mailsystem'])->save();
  }

}
