<?php

declare(strict_types = 1);

namespace Drupal\joinup_video\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\video_embed_field\ProviderManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The filter to normalize embedded videos.
 *
 * This filter should be processed before video_embed_wysiwyg as it parses
 * iframes and should not parse iframes created by other providers to avoid
 * generating duplicated div elements.
 *
 * @Filter(
 *   id = "joinup_video",
 *   title = @Translation("Joinup Video Embed"),
 *   description = @Translation("Enables the use of iframe video embed."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   settings = {
 *     "allowed_providers" = { },
 *     "autoplay" = FALSE,
 *     "responsive" = TRUE,
 *   },
 * )
 */
class JoinupVideo extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The video provider manager.
   *
   * @var \Drupal\video_embed_field\ProviderManagerInterface
   */
  protected $providerManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new JoinupVideo filter.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\video_embed_field\ProviderManagerInterface $provider_manager
   *   The video provider manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ProviderManagerInterface $provider_manager, RendererInterface $renderer, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->providerManager = $provider_manager;
    $this->renderer = $renderer;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('video_embed_field.provider_manager'),
      $container->get('renderer'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getConfiguration()['settings'];
    $form['allowed_providers'] = [
      '#title' => $this->t('Allowed providers'),
      '#description' => $this->t('Allowed providers. If none are selected any video provider can be used.'),
      '#type' => 'checkboxes',
      '#default_value' => $settings['allowed_providers'],
      '#options' => $this->providerManager->getProvidersOptionList(),
    ];
    $form['autoplay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autoplay'),
      '#default_value' => $settings['autoplay'],
      '#description' => $this->t('Autoplay the video when displayed.'),
    ];
    $form['responsive'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Responsive'),
      '#default_value' => $settings['responsive'],
      '#description' => $this->t('Render the video responsive.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $response = new FilterProcessResult($text);

    foreach ($this->getValidMatches($text) as $source_text => $data) {
      if (!$provider = $this->getProvider($data['video_url'])) {
        // No enabled provider knows to handle this URL. Strip out the <iframe>.
        $text = str_replace($source_text, '', $text);
        continue;
      }

      $autoplay = $this->currentUser->hasPermission('never autoplay videos') ? FALSE : $data['settings']['autoplay'];
      $embed_code = $provider->renderEmbedCode($data['settings']['width'], $data['settings']['height'], $autoplay);

      // Add the container to make the video responsive if it's been
      // configured as such. This usually is attached to field output in the
      // case of a formatter, but a custom container must be used where one is
      // not present.
      if ($data['settings']['responsive']) {
        $embed_code = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['video-embed-field-responsive-video'],
          ],
          'children' => $embed_code,
        ];
      }

      // Replace the JSON settings with a video.
      $text = str_replace($source_text, $this->renderer->render($embed_code), $text);

      // Add the required responsive video library only when at least one match
      // is present.
      $response->setAttachments(['library' => ['video_embed_field/responsive-video']]);
      $response->setCacheContexts(['user.permissions']);
    }

    $response->setProcessedText($text);

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  protected function getValidMatches($text) {
    $matches = [];
    $plugin_settings = $this->getConfiguration()['settings'];
    $document = Html::load($text);
    foreach ($document->getElementsByTagName('iframe') as $iframe) {
      /** @var \DOMElement $iframe */
      if ($src = $iframe->getAttribute('src')) {
        $parts = UrlHelper::parse($src);
        $parts['query'] = UrlHelper::filterQueryParameters($parts['query'], [
          'autoplay',
          'autoPlay',
          'autostart',
        ]);
        $url = $this->getUrl($parts);
        $matches[$document->saveHTML($iframe)] = [
          'video_url' => $url,
          'settings' => [
            'width' => $iframe->getAttribute('width'),
            'height' => $iframe->getAttribute('height'),
            'autoplay' => $plugin_settings['autoplay'],
            'responsive' => $plugin_settings['responsive'],
          ],
        ];
      }
    }

    return $matches;
  }

  /**
   * Returns the plugin able to handle this video URL.
   *
   * @param string $url
   *   The video URL.
   *
   * @return \Drupal\video_embed_field\ProviderPluginInterface|null
   *   One of the enabled providers that handles this URL or NULL.
   */
  protected function getProvider($url) {
    /** @var \Drupal\video_embed_field\ProviderPluginBase $provider */
    if (!$provider = $this->providerManager->loadProviderFromInput($url)) {
      return NULL;
    }
    $allowed_providers = array_keys(array_filter($this->getConfiguration()['settings']['allowed_providers']));

    // If no provider was selected, all are available.
    return !$allowed_providers || in_array($provider->getPluginId(), $allowed_providers) ? $provider : NULL;
  }

  /**
   * Attempts to create a URL.
   *
   * @param array $parts
   *   An array of URL parts.
   *
   * @return string|null
   *   The string representation of the URL or NULL if no URL is found.
   */
  protected function getUrl(array $parts): ?string {
    try {
      $url = Url::fromUri($parts['path'], $parts)->toString();
    }
    catch (\Exception $exception) {
      try {
        $path = ltrim($parts['path'], '/');
        $parts['base_url'] = $GLOBALS['base_url'];
        $url = Url::fromUri('internal:/' . $path, $parts)->toString();
      }
      catch (\Exception $exception) {
        return NULL;
      }
    }

    return $url;
  }

}
