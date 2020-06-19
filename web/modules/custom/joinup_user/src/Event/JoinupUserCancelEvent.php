<?php

declare(strict_types = 1);

namespace Drupal\joinup_user\Event;

use Drupal\joinup_user\Entity\JoinupUserInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class representing a 'joinup_user.cancel' event.
 */
class JoinupUserCancelEvent extends Event {

  /**
   * The acount being cancelled.
   *
   * @var \Drupal\joinup_user\Entity\JoinupUserInterface
   */
  protected $account;

  /**
   * Constructs a new event instance.
   *
   * @param \Drupal\joinup_user\Entity\JoinupUserInterface $account
   *   The account being cancelled.
   */
  public function __construct(JoinupUserInterface $account) {
    $this->account = $account;
  }

  /**
   * Returns the account being cancelled.
   *
   * @return \Drupal\joinup_user\Entity\JoinupUserInterface
   *   The user account entity.
   */
  public function getAccount(): JoinupUserInterface {
    return $this->account;
  }

}
