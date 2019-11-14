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
    $user_email = $user->getEmail();
    \Drupal::state()->resetCache();
    $mails = \Drupal::state()->get('system.test_mail_collector');
    if (empty($mails)) {
      throw new \Exception('No mail was sent.');
    }

    return array_filter($mails, function (array $mail) use ($user_email) {
      return $mail['to'] === $user_email;
    });
  }

}
