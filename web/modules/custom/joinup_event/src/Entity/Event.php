<?php

declare(strict_types = 1);

namespace Drupal\joinup_event\Entity;

use Drupal\Core\Url;
use Drupal\joinup_bundle_class\JoinupBundleClassFieldAccessTrait;
use Drupal\node\Entity\Node;

/**
 * Entity subclass for the 'event' bundle.
 */
class Event extends Node implements EventInterface {

  use JoinupBundleClassFieldAccessTrait;

  /**
   * {@inheritdoc}
   */
  public function getLocation(): ?string {
    return $this->getMainPropertyValue('field_location');
  }

  /**
   * {@inheritdoc}
   */
  public function getOnlineLocation(): ?string {
    return $this->getMainPropertyValue('field_event_online_location');
  }

  /**
   * {@inheritdoc}
   */
  public function getWebUrl(): ?Url {
    /** @var \Drupal\link\LinkItemInterface|null $url */
    $url = $this->getFirstItem('field_event_web_url');

    if (!empty($url)) {
      return $url->getUrl();
    }

    return NULL;
  }

}
