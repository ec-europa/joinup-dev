<?php

namespace Drupal\joinup_video\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\video_embed_field\ProviderManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The filter to normalize embedded videos.
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
   * VideoEmbedWysiwyg constructor.
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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ProviderManagerInterface $provider_manager, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->providerManager = $provider_manager;
    $this->renderer = $renderer;
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
      $container->get('renderer')
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
    $settings = $this->getConfiguration()['settings'];

    foreach ($this->getValidMatches($text) as $source_text => $video) {
      if (!$provider = $this->getProvider($video['url'])) {
        // No enabled provider knows to handle this URL. Strip out the <iframe>.
        $text = str_replace($source_text, '', $text);
        continue;
      }

      $embed_code = $provider->renderEmbedCode($video['width'], $video['height'], $settings['autoplay']);

      // Add the container to make the video responsive if it's been configured
      // as such. This usually is attached to field output in the case of a
      // formatter, but a custom container must be used where one isn't present.
      if ($settings['responsive']) {
        $embed_code = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['video-embed-field-responsive-video'],
          ],
          'children' => $embed_code,
        ];
      }

      // Replace the source <iframe> with the generate one video.
      $text = str_replace($source_text, $this->renderer->render($embed_code), $text);

      // Add the required responsive video library.
      $response->setAttachments(['library' => ['video_embed_field/responsive-video']]);
    }

    $response->setProcessedText($text);

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  protected function getValidMatches($text) {
    $matches = [];
    $document = Html::load($text);
    foreach ($document->getElementsByTagName('iframe') as $iframe) {
      /** @var \DOMElement $iframe */
      $src = $iframe->getAttribute('src');
      $options = UrlHelper::parse($src);
      $options['query'] = UrlHelper::filterQueryParameters($options['query'], [
        'autoplay',
        'autoPlay',
        'autostart',
      ]);
      $url = $this->getAbsoluteUrl($options);
      $matches[$document->saveHTML($iframe)] = [
        'url' => $url,
        'width' => $iframe->getAttribute('width'),
        'height' => $iframe->getAttribute('height'),
      ];
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
   * Attempts to create the absolute url.
   *
   * @param array $options
   *   An array of options including the path.
   *
   * @return string|null
   *   The string representation of the url or null if no url is found.
   */
  protected function getAbsoluteUrl(array $options) {
    try {
      $url = Url::fromUri($options['path'], $options)->toString();
    }
    catch (\Exception $e) {
      try {
        $url = ltrim($options['path'], '/');
        $url = Url::fromUri('internal:/' . $url, $options)->setAbsolute(TRUE)->toString();
      }
      catch (\Exception $e) {
        return NULL;
      }
    }

    return $url;
  }

}
