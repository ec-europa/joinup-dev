<?php

/**
 * @file
 * Contains \JoinupNotificationSubContext.
 */

use Behat\Gherkin\Node\TableNode;
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
   * Asserts that an email has been sent.
   *
   * @param \Behat\Gherkin\Node\TableNode $table
   *   The mail data table.
   *
   * @Then the following email should have been sent:
   *
   * @throws \Exception
   *   Throws an exception when a parameter is not the expected one.
   */
  public function assertEmailSent(TableNode $table) {
    $values = $table->getRowsHash();
    $recipient = $values['recipient'];
    $message_template_id = $values['template'];
    $subject = $values['subject'];
    $body = $values['body'];

    $user = user_load_by_name($recipient);
    if (empty($user)) {
      throw new \Exception("User {$recipient} was not found.");
    }
    if (empty($user->getEmail())) {
      throw new \Exception("User {$recipient} does not have an email.");
    }

    \Drupal::state()->resetCache();
    $mails = \Drupal::state()->get('system.test_mail_collector');
    $last_mail = end($mails);
    if (!$last_mail) {
      throw new Exception('No mail was sent.');
    }

    if ($last_mail['to'] != $user->getEmail()) {
      throw new \Exception("Unexpected recipient: " . $last_mail['to']);
    }

    $message_template = MessageTemplate::load($last_mail['key']);
    if ($message_template_id !== $message_template->getDescription()) {
      throw new \Exception("Unexpected template description.\nFound value: {$message_template->getDescription()}");
    };

    $last_mail['subject'] = trim($last_mail['subject']);
    if ($subject !== $last_mail['subject']) {
      throw new \Exception("Unexpected mail subject.\nFound: {$last_mail['subject']}");
    }

    // \Drupal\Core\Mail\Plugin\Mail\PhpMail::format() automatically wraps the
    // mail body line to a certain amount of characters (77 by default). Spaces
    // are also removed.
    // @see \Drupal\Core\Mail\Plugin\Mail\PhpMail::format()
    $last_mail['body'] = str_replace("\n", ' ', $last_mail['body']);
    // Since the body field has url tokens, we cannot provide a full body text
    // because we don't have the url available. Because of this, we just
    // match partial text.
    if (strpos($last_mail['body'], $body) === FALSE) {
      throw new \Exception("Expected body text was not found in:\n{$last_mail['body']}");
    }

    // Reset the email storage.
    \Drupal::state()->set('system.test_mail_collector', []);
    \Drupal::state()->resetCache();
  }

  /**
   * Clears the collected e-mails.
   *
   * This step is useful to test that after a certain action, only the expected
   * mails are sent.
   *
   * @Then all (the )e-mails have been sent
   */
  public function clearMailCollector() {
    \Drupal::state()->set('system.test_mail_collector', []);
    \Drupal::state()->resetCache();
  }

  /**
   * Asserts that a certain amount of e-mails have been sent.
   *
   * @param int $count
   *   The expected count of e-mails sent.
   *
   * @throws \Exception
   *   Thrown when the count doesn't match the actual number.
   *
   * @Then :count e-mail(s) should have been sent
   */
  public function assertNumberOfEmailSent($count) {
    $mails = \Drupal::state()->get('system.test_mail_collector');

    if (count($mails) != $count) {
      throw new \Exception("Invalid number of e-mail sent. Expected $count, sent " . count($mails));
    }
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
    \Drupal::state()->set('system.test_mail_collector', []);
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
