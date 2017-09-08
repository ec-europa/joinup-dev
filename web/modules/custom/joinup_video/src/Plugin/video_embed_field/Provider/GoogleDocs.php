<?php

namespace Drupal\joinup_video\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;
use GuzzleHttp\TransferStats;

/**
 * A docs.google.com presentation plugin provider.
 *
 * @VideoEmbedProvider(
 *   id = "google_docs",
 *   title = @Translation("Google document (docs.google.com)")
 * )
 */
class GoogleDocs extends ProviderPluginBase {

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
      '#provider' => 'google_docs',
      '#url' => static::$resolvedUrl[$this->getInput()],
      '#query' => [
        'ref' => $this->getVideoId(),
        'autoplay' => $autoplay ? 'true' : 'false',
      ],
      '#attributes' => [
        'width' => $width,
        'height' => $height,
        'frameborder' => '0',
        'allowfullscreen' => 'true',
        'mozallowfullscreen' => 'true',
        'webkitallowfullscreen' => 'true',
      ],
    ];

    return $iframe;
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    // The URL may be an European Union short URL. Resolve it.
    if (preg_match('#^(?:(?:https?:)?//)?docs\.google\.com/.*?/d/(e/)?[0-9a-z]+(/viewform?.*)?$#i', $input)) {
      if (!isset(static::$resolvedUrl[$input])) {
        /** @var \Psr\Http\Message\UriInterface $uri */
        \Drupal::httpClient()->get($input, [
          'on_stats' => function (TransferStats $stats) use (&$uri) {
            $uri = $stats->getEffectiveUri();
          },
]
        );
        static::$resolvedUrl[$input] = $uri ? $uri->__toString() : $input;
      }
      $input = static::$resolvedUrl[$input];
    }

    preg_match('#^(?:(?:https?:)?//)?docs\.google\.com/.*?/d/(e/)?(?<id>[^&\?/]+)(/viewform?.*)?#i', $input, $matches);
    return isset($matches['id']) ? $matches['id'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    return '';
  }

}
