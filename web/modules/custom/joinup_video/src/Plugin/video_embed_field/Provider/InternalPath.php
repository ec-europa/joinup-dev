<?php

declare(strict_types = 1);

namespace Drupal\joinup_video\Plugin\video_embed_field\Provider;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use Drupal\video_embed_field\ProviderPluginBase;

/**
 * An iframe pointing to site itself.
 *
 * @VideoEmbedProvider(
 *   id = "internal_path",
 *   title = @Translation("Internal path")
 * )
 */
class InternalPath extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    $iframe = [
      '#type' => 'video_embed_iframe',
      '#provider' => 'internal_path',
      '#url' => Url::fromUri('internal:/' . $this->getVideoId())
        ->setOption('base_url', $GLOBALS['base_url'])
        ->setAbsolute()
        ->toString(),
    ];

    return $iframe;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable($input) {
    return !UrlHelper::isExternal($input) || UrlHelper::externalIsLocal($input, $GLOBALS['base_url']);
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input): string {
    if (strpos($input, $GLOBALS['base_url']) === 0) {
      $input = substr($input, strlen($GLOBALS['base_url']));
    }
    elseif (strpos($input, base_path()) === 0) {
      $input = substr($input, strlen(base_path()) - 1);
    }
    return ltrim($input, '/');
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    return '';
  }

}
