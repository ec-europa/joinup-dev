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
   * Table format:
   * | template  | Comment deletion |
   * | recipient | username0123     |
   * | subject   | The mail subject |
   * | body      | The message body |
   *
   * The 'template' can be either the name or the description of the message
   * template. If the template row is not present, no matching on template will
   * be done. This is needed for system mails (like the ones sent by the user
   * module).
   * The 'body' is a partial text based match.
   *
   * @param \Behat\Gherkin\Node\TableNode $table
   *   The mail data table.
   *
   * @throws \Exception
   *   Throws an exception when a parameter is not the expected one.
   *
   * @Then the following email should have been sent:
   */
  public function assertEmailSent(TableNode $table) {
    /** @var string $body */
    /** @var string $recipient */
    /** @var string $subject */
    /** @var string $template */
    extract($table->getRowsHash());

    $user = user_load_by_name($recipient);
    if (empty($user)) {
      throw new \Exception("User $recipient was not found.");
    }
    if (empty($user->getEmail())) {
      throw new \Exception("User $recipient does not have an e-mail address.");
    }

    \Drupal::state()->resetCache();
    $mails = \Drupal::state()->get('system.test_mail_collector');
    if (empty($mails)) {
      throw new Exception('No mail was sent.');
    }

    $email_found = FALSE;
    foreach ($mails as $mail) {
      if ($mail['to'] !== $user->getEmail()) {
        continue;
      }

      if ($subject !== trim($mail['subject'])) {
        continue;
      }

      // \Drupal\Core\Mail\Plugin\Mail\PhpMail::format() automatically wraps the
      // mail body line to a certain amount of characters (77 by default).
      // Spaces are also removed.
      // @see \Drupal\Core\Mail\Plugin\Mail\PhpMail::format()
      $mail_body = str_replace("\n", ' ', $mail['body']);
      $mail_body = trim($mail_body);
      // Since the body field has url tokens, we cannot provide a full body text
      // because we don't have the url available. Because of this, we just
      // match partial text.
      if (strpos($mail_body, $body) === FALSE) {
        continue;
      }

      // If the template is present, try to load the related message template
      // entity.
      if (isset($template)) {
        $message_template = MessageTemplate::load($mail['key']);
        if ($template !== $message_template->getDescription() && $template !== $message_template->getLabel()) {
          continue;
        };
      }

      // We found a match. Stop searching.
      $email_found = TRUE;
      break;
    }

    PHPUnit_Framework_Assert::assertTrue($email_found, "Did not found expected email to '$recipient' with subject '$subject'.");
  }

  /**
   * Clears the cache of the mail collector.
   *
   * Use this if you don't want to match on emails that have been sent earlier
   * in the scenario.
   *
   * @Given the mail collector cache is empty
   * @Given all (the )e-mails have been sent
   */
  public function clearMailCollectorCache() {
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
    $this->mailConfig = \Drupal::configFactory()->getEditable('mailsystem.settings');
    $this->savedMailDefaults = $this->mailConfig->get('defaults.sender');
    $this->mailConfig
      ->set('defaults.sender', 'test_mail_collector')
      ->save();
    \Drupal::state()->set('system.test_mail_collector', []);
  }

  /**
   * Restores the mailing system settings with the default one.
   *
   * @AfterScenario @email
   */
  public function afterEmailScenario() {
    $this->mailConfig->set('defaults.sender', $this->savedMailDefaults)->save();
  }

}
