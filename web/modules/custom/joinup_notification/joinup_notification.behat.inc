<?php

/**
 * @file
 * Contains \JoinupNotificationSubContext.
 */

use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\message\Entity\MessageTemplate;

/**
 * Behat step definitions for testing notifications.
 */
class JoinupNotificationSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  /**
   * The mail system storage settings.
   *
   * @var \Drupal\Core\Config\StorableConfigBase
   */
  protected $mailConfig;

  /**
   * Holds the default settings for the mail server so a revert is possible.
   *
   * @var array
   */
  protected $savedMailDefaults;

  /**
   * Asserts that an email is sent to the given user.
   *
   * @param string $recipient
   *    The username of the recipient of the email.
   * @param string $description
   *    The description of the message entity.
   *
   * @Then an email should be sent to :recipient described as :description
   *
   * @throws \Exception
   *    Throws an exception when a user is not found or his email is empty.
   *    Throws an exception also when the mail is not sent or the recipient is
   *    not the expected one.
   */
  public function anEmailShouldBeSentTo($recipient, $description) {
    $user = user_load_by_name($recipient);
    if (empty($user)) {
      throw new \Exception("User {$recipient} was not found.");
    }
    if (empty($user->getEmail())) {
      throw new \Exception("User {$recipient} does not have an email.");
    }

    $mails = \Drupal::state()->get('system.test_mail_collector');
    $last_mail = end($mails);
    if (!$last_mail) {
      throw new Exception('No mail was sent.');
    }

    if ($last_mail['to'] != $user->getEmail()) {
      throw new \Exception("Unexpected recipient: " . $last_mail['to']);
    }

    $message_template = MessageTemplate::load($last_mail['key']);
    if ($description !== $message_template->getDescription()) {
      throw new \Exception("Unexpected template description.\nFound value: {$message_template->getDescription()}\nExpected value: {$description}");
    };

    // Reset the email storage.
    \Drupal::state()->set('system.test_mail_collector', array());
    \Drupal::state()->resetCache();
  }

  /**
   * Swaps the mailing system settings with a test one.
   *
   * @BeforeScenario @email
   */
  public function beforeEmailScenario() {
    $this->mailConfig = \Drupal::configFactory()->getEditable('system.mail');
    $this->savedMailDefaults = $this->mailConfig->get('interface.default');
    $this->mailConfig->set('interface.default', 'test_mail_collector')->save();
    \Drupal::state()->set('system.test_mail_collector', array());
  }

  /**
   * Restores the mailing system settings with the default one.
   *
   * @AfterScenario @email
   */
  public function afterEmailScenario() {
    $this->mailConfig->set('interface.default', $this->savedMailDefaults)->save();
  }

}
