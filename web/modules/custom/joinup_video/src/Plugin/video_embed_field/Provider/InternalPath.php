<?php

namespace Drupal\joinup_video\Plugin\video_embed_field\Provider;

use Drupal\Core\Url;
use Drupal\video_embed_field\ProviderPluginBase;
use GuzzleHttp\ClientInterface;

/**
 * An European Commission video provider plugin.
 *
 * @VideoEmbedProvider(
 *   id = "internal_path",
 *   title = @Translation("Internal path")
 * )
 */
class InternalPath extends ProviderPluginBase {

  /**
   * The base url of the input.
   *
   * @var string
   */
  protected $baseUrl;

  /**
   * Static cache for resolved short URLs.
   *
   * @var string[]
   */
  protected static $resolvedUrl = [];

  /**
   * Create a plugin with the given input.
   *
   * @param string $configuration
   *   The configuration of the plugin.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   An HTTP client.
   */
  public function __construct($configuration, $plugin_id, array $plugin_definition, ClientInterface $http_client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $http_client);
    $this->baseUrl = $this->getUrlFromInput($configuration['input']);
  }

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    $iframe = [
      '#type' => 'video_embed_iframe',
      '#provider' => 'internal_path',
      '#url' => Url::fromUserInput('/' . ltrim($this->getVideoId(), '/'))->setAbsolute(TRUE)->toString(),
    ];

    return $iframe;
  }

  /**
   * {@inheritdoc}
   *
   * The internal provider needs the url to match the base url to be applicable.
   */
  public static function isApplicable($input) {
    $applicable = parent::isApplicable($input);
    $url = static::getUrlFromInput($input);
    return $applicable && !empty($url) && $url === \Drupal::request()->getHost();
  }

  /**
   * Parses the input and returns a list of matches.
   *
   * @param string $input
   *   The input url.
   *
   * @return array
   *   An array of matches related to the url. The two specific values returned
   *   are id and base_url.
   */
  public static function getDataFromInput($input) {
    preg_match('#^(?:(?:https?:)?//)(?<base_url>[^/]+)/(index\.php\?q=)?(?<id>[^&\?]+)#i', $input, $matches);
    return $matches;
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    $matches = static::getDataFromInput($input);
    return isset($matches['id']) ? $matches['id'] : FALSE;
  }

  /**
   * Return the base_url from the input.
   *
   * @param string $input
   *   The input url.
   *
   * @return mixed
   *   The base url.
   */
  public static function getUrlFromInput($input) {
    $matches = static::getDataFromInput($input);
    return isset($matches['base_url']) ? $matches['base_url'] : FALSE;
  }

  /**
   * Returns the base url.
   *
   * @return string
   *   The base url.
   */
  public function getBaseUrl() {
    return $this->baseUrl;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    return '';
  }

}
