<?php

declare(strict_types = 1);

namespace Drupal\contact_form\Plugin\Menu;

use Drupal\joinup_core\Plugin\Menu\DestinationAwareMenuLinkBase;

/**
 * Provides a custom menu link that leads to the Contact Joinup Support form.
 */
class ContactSupportMenuLink extends DestinationAwareMenuLinkBase {

  /**
   * {@inheritdoc}
   */
  public function getOptions(): array {
    $options = parent::getOptions();
    $options['query'] = $this->getDestinationArray() + $options['query'];
    return $options;
  }

}
