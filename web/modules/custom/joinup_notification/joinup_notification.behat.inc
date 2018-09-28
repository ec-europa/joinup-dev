<?php

declare(strict_types = 1);

/**
 * @file
 * Contains \JoinupNotificationSubContext.
 */

use Behat\Gherkin\Node\TableNode;
use Drupal\Component\Render\MarkupInterface;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\ConfigReadOnlyTrait;
use Drupal\message\Entity\MessageTemplate;
use PHPUnit\Framework\Assert;

/**
 * Behat step definitions for testing notifications.
 */
class JoinupNotificationSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use ConfigReadOnlyTrait;

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
   * | template           | Comment deletion |
   * | recipient          | username0123     |
   * | recipient_mail     | usertest@ex.com  |
   * | subject            | The mail subject |
   * | body               | The message body |
   * | html               | yes/no           |
   * | signature_required | yes/no           |
   *
   * - The 'template' can be either the name or the description of the message
   *   template. If the template row is not present, no matching on template
   *   will be done. This is needed for system mails (like the ones sent by the
   *   user module).
   * - The recipient and recipient_mail are not both mandatory. If a
   *   recipient_mail is not used, the user will be loaded and his email will be
   *   used.
   * - The 'body' is a partial text based match.
   * - The 'html' and 'signature_required' columns can be either 'yes' or 'no'.
   *   When not present, 'yes' is assumed.
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
    /** @var string $template */
    /** @var string $recipient */
    /** @var string $recipient_mail */
    /** @var string $from */
    /** @var string $body */
    /** @var string $subject */
    /** @var string $signature_required */
    extract($table->getRowsHash());

    // Translate the human readable value for the requirement of the signature
    // to a boolean. This defaults to TRUE.
    $signature_required = !isset($signature_required) || $signature_required !== 'no';

    // If the 'html' row is not present, assume a 'yes'. Convert 'yes' to TRUE
    // and 'no' to FALSE.
    $html = empty($html) || $html === 'yes';

    if (!empty($recipient_mail)) {
      if (!filter_var($recipient_mail, FILTER_VALIDATE_EMAIL)) {
        throw new \Exception("Recipient {$recipient_mail} is not a valid e-mail address.");
      }
      $recipient = $recipient_mail;
    }
    else {
      $user = user_load_by_name($recipient);
      if (empty($user)) {
        throw new \Exception("User $recipient was not found.");
      }
      if (empty($user->getEmail())) {
        throw new \Exception("User $recipient does not have an e-mail address.");
      }
      $recipient = $user->getEmail();
    }

    $mails = $this->getEmailsBySubjectAndMail($subject, $recipient);
    $email_found = FALSE;
    foreach ($mails as $mail) {
      // Check the sender email if it is set.
      if (!empty($from) && $mail['from'] !== $from) {
        continue;
      }

      // \Drupal\Core\Mail\Plugin\Mail\PhpMail::format() automatically wraps the
      // mail body line to a certain amount of characters (77 by default).
      // Spaces are also removed.
      // @see \Drupal\Core\Mail\Plugin\Mail\PhpMail::format()
      $mail_body = trim($mail['body']);
      $mail_body = preg_replace("/[\r\n\t]+/", ' ', $mail_body);

      // If the original mail body is markup, we need to:
      // - undo the encoding done by Twig to quotes;
      // - remove the HTML to simplify the matching on the body text.
      // @see vendor/twig/twig/lib/Twig/Extension/Core.php:1034
      if ($mail['body'] instanceof MarkupInterface) {
        $mail_body = htmlspecialchars_decode($mail_body, ENT_QUOTES | ENT_SUBSTITUTE);
        $mail_body = strip_tags($mail_body);
      }

      // Ensure that there are no un-replaced arguments in the mail subject and
      // body.
      if (preg_match('/\B@[^\B]+/', $mail_body) || preg_match('/\B@[^\B]+/', $mail['subject'])) {
        throw new \Exception('There were arguments found in the subject or the body of the email that were not replaced.');
      }

      // Check the existence or absence of the signature.
      $signature_present = preg_match('/Kind regards,\s{0,2}The Joinup Support Team/', $mail_body);
      if ($signature_required && !$signature_present) {
        throw new \Exception('The signature of the email was not found or is not correct.');
      }
      elseif (!$signature_required && $signature_present) {
        throw new \Exception('The signature of the email was found in the email but should not be present.');
      }

      if (isset($body)) {
        // Since the body field has url tokens, we cannot provide a full body
        // text because we don't have the url available. Because of this, we
        // just match partial text.
        $body = preg_replace("/\s+/", ' ', $body);
        $mail_body = preg_replace("/\s+/", ' ', $mail_body);
        if (strpos($mail_body, $body) === FALSE) {
          continue;
        }
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

    Assert::assertTrue($email_found, "Did not find expected email to '$recipient' with subject '$subject'.");

    // If the previous assertion passed, the $mail variable contains the correct
    // mail.
    /** @var array $mail */
    if ($html) {
      Assert::assertEquals(SWIFTMAILER_FORMAT_HTML, $mail['headers']['Content-Type'], 'The mail is not sent as HTML.');
    }
    else {
      Assert::assertStringStartsWith(SWIFTMAILER_FORMAT_PLAIN, $mail['headers']['Content-Type'], 'The mail is not sent as plain text.');
    }
  }

  /**
   * Asserts that an email has not been sent.
   *
   * See ::assertEmailSent() for the structure of the table node argument.
   *
   * @param \Behat\Gherkin\Node\TableNode $table
   *   The mail data table.
   *
   * @throws \Exception
   *   Throws an exception when a parameter is not the expected one.
   *
   * @see self::assertEmailSent()
   *
   * @Then the following email should not have been sent:
   */
  public function assertEmailNotSent(TableNode $table) {
    try {
      // Reusing ::assertEmailSent().
      $this->assertEmailSent($table);
    }
    catch (\Exception $e) {
      // If the assertion above throws an exception, it means that the email
      // was not sent, so we can return.
      return;
    }

    throw new \Exception('E-mail was sent.');
  }

  /**
   * Asserts that an email has been sent and contains some pieces of text.
   *
   * @param string $user
   *   The user name.
   * @param string $subject
   *   The subject of the email.
   * @param \Behat\Gherkin\Node\TableNode $table
   *   The mail data table.
   *
   * @throws \Exception
   *   Throws an exception when a parameter is not the expected one.
   *
   * @Then the email sent to :user with subject :subject contains the( following lines of) text:
   */
  public function assertEmailSentAndContainsText(string $user, string $subject, TableNode $table) {
    $lines_of_text = $table->getColumnsHash();
    $user = user_load_by_name($user);
    $recipient = $user->getEmail();
    $mails = $this->getEmailsBySubjectAndMail($subject, $recipient);
    $email_found = FALSE;
    foreach ($mails as $mail) {
      // \Drupal\Core\Mail\Plugin\Mail\PhpMail::format() automatically wraps the
      // mail body line to a certain amount of characters (77 by default).
      // Spaces are also removed.
      // @see \Drupal\Core\Mail\Plugin\Mail\PhpMail::format()
      $mail_body = preg_replace("/[\r\n\s\t]+/", ' ', $mail['body']);
      $mail_body = trim($mail_body);

      // If the original mail body is markup, we need to:
      // - undo the encoding done by Twig to quotes;
      // - remove the HTML to simplify the matching on the body text.
      // @see vendor/twig/twig/lib/Twig/Extension/Core.php:1034
      if ($mail['body'] instanceof MarkupInterface) {
        $mail_body = htmlspecialchars_decode($mail_body, ENT_QUOTES | ENT_SUBSTITUTE);
        $mail_body = strip_tags($mail_body);
      }

      foreach ($lines_of_text as $line_of_text) {
        $text = $line_of_text['text'];
        if (strpos($mail_body, $text) === FALSE) {
          continue 2;
        }
      }

      // We found a match. Stop searching.
      $email_found = TRUE;
      break;
    }

    Assert::assertTrue($email_found, "Did not find expected email to '$recipient' with subject '$subject'.");
  }

  /**
   * Fetches the emails sent given criteria.
   *
   * @param string $subject
   *   The subject of the email sent.
   * @param string $recipient_mail
   *   The email of the recipient.
   *
   * @return array
   *   An array of emails found.
   *
   * @throws \Exception
   *   Thrown if no emails are found or no user exists with the given data.
   */
  protected function getEmailsBySubjectAndMail(string $subject, string $recipient_mail): array {
    \Drupal::state()->resetCache();
    $mails = \Drupal::state()->get('system.test_mail_collector');
    if (empty($mails)) {
      throw new \Exception('No mail was sent.');
    }

    $emails_found = [];
    foreach ($mails as $mail) {
      if ($mail['to'] !== $recipient_mail) {
        continue;
      }

      if ($subject !== trim($mail['subject'])) {
        continue;
      }

      $emails_found[] = $mail;
    }

    if (empty($emails_found)) {
      throw new Exception("No emails found sent to {$recipient_mail} with subject '{$subject}'.");
    }

    return $emails_found;
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
   * Clicks on the mail change link received via a verification E-mail.
   *
   * @param string $mail
   *   The recipient E-mail address.
   *
   * @throws \Exception
   *   If no mail change verification E-mail was sent to the given address or
   *   the E-mail doesn't contain a valid verification link.
   *
   * @Given I click the mail change link from the email sent to :mail
   */
  public function clickMailChangeLinkFromMail(string $mail): void {
    $found = FALSE;
    foreach (\Drupal::state()->get('system.test_mail_collector', []) as $mail_sent) {
      if ($mail_sent['key'] === 'mail_change_verification' && $mail_sent['to'] === $mail) {
        $found = TRUE;
        break;
      }
    }

    if (!$found) {
      throw new \Exception("No mail change verification E-mail has been sent to $mail.");
    }

    $pattern = '#https?://[^/].*/user/mail-change/[^/].*/[^/].*/[^/].*/.*#';
    if (!preg_match($pattern, $mail_sent['plain'], $match)) {
      throw new \Exception("The mail change verification E-mail doesn't contain a valid verification link.");
    }

    $this->visitPath($match[0]);
  }

  /**
   * Swaps the mailing system settings with a test one.
   *
   * @BeforeScenario @email
   */
  public function beforeEmailScenario() {
    // Temporarily bypass read only config so that we can set up the testing
    // mail system.
    $this->bypassReadOnlyConfig();

    $this->mailConfig = \Drupal::configFactory()->getEditable('mailsystem.settings');
    $this->savedMailDefaults = $this->mailConfig->get('defaults.sender');
    $this->mailConfig
      ->set('defaults.sender', 'test_mail_collector')
      ->save();
    \Drupal::state()->set('system.test_mail_collector', []);
    \Drupal::state()->resetCache();

    $this->restoreReadOnlyConfig();
  }

  /**
   * Restores the mailing system settings with the default one.
   *
   * @AfterScenario @email
   */
  public function afterEmailScenario() {
    // Temporarily bypass read only config so that we can restore the original
    // mail handler.
    $this->bypassReadOnlyConfig();

    $this->mailConfig->set('defaults.sender', $this->savedMailDefaults)->save();

    $this->restoreReadOnlyConfig();
  }

}
