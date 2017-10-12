<?php

namespace Drupal\joinup_video\Plugin\video_embed_field\Provider;

use GuzzleHttp\ClientInterface;
use Drupal\video_embed_field\ProviderPluginBase;

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
   * The google document type.
   *
   * @var string
   */
  protected $docType;

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
    $this->docType = $this->getTypeFromInput($configuration['input']);
  }

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    $iframe = [
      '#type' => 'video_embed_iframe',
      '#provider' => 'google_docs',
      '#url' => sprintf('https://docs.google.com/%s/d/%s/viewform?embedded=true', $this->getDocType(), $this->getVideoId()),
    ];

    return $iframe;
  }

  /**
   * Parses the input and returns a list of matches.
   *
   * @param string $input
   *   The input url.
   *
   * @return array
   *   An array of matches related to the url. The two specific values returned
   *   are id and type.
   */
  public static function getDataFromInput($input) {
    preg_match('#^(?:(?:https?:)?//)?docs\.google\.com/(?<type>[^/]+)/d/(e/)?(?<id>[^/]+).*?#i', $input, $matches);
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
   * Return the google document type.
   *
   * @param string $input
   *   The input url.
   *
   * @return mixed
   *   The type of the document, e.g. forms or presentation. False if there is
   *   no match found.
   */
  public static function getTypeFromInput($input) {
    $matches = static::getDataFromInput($input);
    return isset($matches['type']) ? $matches['type'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    return '';
  }

  /**
   * Returns the document type.
   *
   * @return string
   *   The document type.
   */
  public function getDocType() {
    return $this->docType;
  }

}
