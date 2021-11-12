<?php

declare(strict_types = 1);

namespace Drupal\joinup\Traits;

use Drupal\Component\Render\MarkupInterface;
use Drupal\user\UserInterface;

/**
 * Contains utility methods.
 */
trait MailCollectorTrait {

  /**
   * Formats a mail body to a simple string.
   *
   * @param \Drupal\Component\Render\MarkupInterface $markup
   *   The mail body markup.
   *
   * @return string
   *   The simplified mail body.
   */
  protected function formatMailBodyMarkup(MarkupInterface $markup): string {
    $mail_body = $this->formatMailBodyText((string) $markup);
    // For formattable markups, we also need to:
    // - undo the encoding done by Twig to quotes;
    // - remove the HTML to simplify the matching on the body text.
    // @see vendor/twig/twig/lib/Twig/Extension/Core.php:1034
    $mail_body = htmlspecialchars_decode($mail_body, ENT_QUOTES | ENT_SUBSTITUTE);
    return strip_tags($mail_body);
  }

  /**
   * Strips and formats a mail body to a format used in tests.
   *
   * @param string $mail_body
   *   The mail body.
   *
   * @return string
   *   The formatted.
   */
  protected function formatMailBodyText(string $mail_body): string {
    // \Drupal\Core\Mail\Plugin\Mail\PhpMail::format() automatically wraps the
    // mail body line to a certain amount of characters (77 by default).
    // Spaces are also removed.
    // @see \Drupal\Core\Mail\Plugin\Mail\PhpMail::format()
    $mail_body = str_replace("\r\n", "\n", $mail_body);
    $mail_body = preg_replace("/[\n\s\t]+/", ' ', $mail_body);
    return trim($mail_body);
  }

  /**
   * Retrieves emails sent to the user from the email collection.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user for which to retrieve emails.
   *
   * @return array
   *   An array of mails as stored in the email collector.
   *
   * @throws \Exception
   *   Thrown if no emails have been sent.
   */
  protected function getUserMails(UserInterface $user): array {
    $mails = $this->getMails();
    if (empty($mails)) {
      throw new \Exception('No mail was sent.');
    }

    $user_email = $user->getEmail();
    return array_filter($mails, function (array $mail) use ($user_email) {
      return $mail['to'] === $user_email;
    });
  }

  /**
   * Fetches the emails sent given criteria.
   *
   * @param string $subject
   *   The subject of the email sent.
   * @param string $recipient_mail
   *   The email of the recipient.
   * @param bool $strict
   *   When set to TRUE will throw an exception if no mails are found with the
   *   given subject and mail. Defaults to TRUE.
   *
   * @return array
   *   An array of emails found.
   *
   * @throws \Exception
   *   Thrown if no emails are found or no user exists with the given data.
   */
  protected function getEmailsBySubjectAndMail(string $subject, string $recipient_mail, bool $strict = TRUE): array {
    $mails = $this->getMails();
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

    if (empty($emails_found) && $strict) {
      throw new \Exception("No emails found sent to {$recipient_mail} with subject '{$subject}'.");
    }

    return $emails_found;
  }

  /**
   * Returns all collected emails.
   *
   * @return array
   *   All the mails stored in the mail collector.
   */
  protected function getMails(): array {
    \Drupal::state()->resetCache();
    return \Drupal::state()->get('system.test_mail_collector', []);
  }

}
