<?php

declare(strict_types = 1);

namespace Drupal\joinup_eulogin\Plugin\Menu;

use Drupal\joinup_core\Plugin\Menu\DestinationAwareMenuLinkBase;

/**
 * Provides a custom menu link that leads to the EULogin login form.
 */
class EuLoginMenuLink extends DestinationAwareMenuLinkBase {

  /**
   * {@inheritdoc}
   */
  public function isEnabled(): bool {
    return $this->currentUser->isAnonymous();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    $cache = parent::getCacheContexts();
    // This is different on every URL, including query arguments, and is hidden
    // for authenticated users.
    $cache[] = 'user.roles:authenticated';

    return $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(): array {
    $options = parent::getOptions();

    $options['query']['returnto'] = $this->getRedirectDestination()->get();
    unset($options['query']['destination']);

    return $options;
  }

}
