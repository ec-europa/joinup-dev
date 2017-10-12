<?php

namespace Drupal\joinup_video\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;

/**
 * A slideshare.net presentation plugin provider.
 *
 * @VideoEmbedProvider(
 *   id = "slideshare",
 *   title = @Translation("Slideshare presentation")
 * )
 */
class Slideshare extends ProviderPluginBase {

  /**
   * Static cache for resolved short URLs.
   *
   * @var string[]
   */
  protected static $resolvedUrl = [];

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    $iframe = [
      '#type' => 'video_embed_iframe',
      '#provider' => 'slideshare',
      '#url' => sprintf('https://www.slideshare.net/slideshow/embed_code/key/%s', $this->getVideoId()),
    ];

    return $iframe;
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    preg_match('#^(?:(?:https?:)?//)?(www\.)slideshare\.net/slideshow/embed_code/key/(?<id>[^&\?/]+)#i', $input, $matches);
    return isset($matches['id']) ? $matches['id'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    return '';
  }

}
