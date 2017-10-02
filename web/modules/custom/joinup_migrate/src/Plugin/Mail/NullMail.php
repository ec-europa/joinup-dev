<?php

namespace Drupal\joinup_migrate\Plugin\Mail;

use Drupal\Core\Mail\MailInterface;

/**
 * Null mail backend.
 *
 * @Mail(
 *   id = "null",
 *   label = @Translation("Null mail backend"),
 *   description = @Translation("A mail backend that does nothing.")
 * )
 */
class NullMail implements MailInterface {

  /**
   * {@inheritdoc}
   */
  public function format(array $message) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function mail(array $message) {
    return TRUE;
  }

}
