<?php

namespace Drupal\joinup_core\Plugin\SocialMediaLinks\Iconset;

use Drupal\Component\Utility\Html;
use Drupal\social_media_links\IconsetBase;

/**
 * Provides the Joinup icons set.
 *
 * Since the user social media field requires this class in its configuration,
 * we cannot put this in the Joinup profile.
 *
 * @Iconset(
 *   id = "joinup",
 *   publisher = "Joinup",
 *   name = "Joinup icons",
 * )
 */
class JoinupIcons extends IconsetBase {

  /**
   * {@inheritdoc}
   */
  public function getStyle() {
    return [
      'default' => 'default',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIconPath($icon_name, $style) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return 'library';
  }

  /**
   * {@inheritdoc}
   */
  public function getIconElement($platform, $style) {
    // The $platform variable is actually an instance of the platform itself.
    // The social_media_links module documentation is outdated.
    /** @var \Drupal\social_media_links\PlatformInterface $platform */
    $class = Html::cleanCssIdentifier($platform->getIconName());

    $icon = [
      '#type' => 'inline_template',
      '#template' => '<span class="icon icon--{{ class }}"></span>',
      '#context' => ['class' => $class],
    ];

    return $icon;
  }

}
