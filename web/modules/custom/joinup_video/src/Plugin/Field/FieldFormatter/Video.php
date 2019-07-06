<?php

namespace Drupal\joinup_video\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\video_embed_field\Plugin\Field\FieldFormatter\Video as OriginalVideo;
use Drupal\video_embed_field\ProviderManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Plugin implementation of the video field formatter that uses the cck.
 */
class Video extends OriginalVideo {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * Constructs a new instance of the plugin.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\video_embed_field\ProviderManagerInterface $provider_manager
   *   The video embed provider manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The logged in user.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   The request stack.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, $settings, $label, $view_mode, $third_party_settings, ProviderManagerInterface $provider_manager, AccountInterface $current_user, RequestStack $request) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $provider_manager, $current_user);
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('video_embed_field.provider_manager'),
      $container->get('current_user'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    foreach ($items as $delta => $item) {
      $provider = $this->providerManager->loadProviderFromInput($item->value);

      if (!$provider) {
        $element[$delta] = ['#theme' => 'video_embed_field_missing_provider'];
      }
      else {
        $autoplay = $this->currentUser->hasPermission('never autoplay videos') ? FALSE : $this->getSetting('autoplay');
        $element[$delta] = $provider->renderEmbedCode($this->getSetting('width'), $this->getSetting('height'), $autoplay);
        $element[$delta]['#cache']['contexts'][] = 'user.permissions';

        // Override the default url and pass it to the ec cck url.
        if (!UrlHelper::externalIsLocal($element[$delta]['#url'], $this->request->getCurrentRequest()->getSchemeAndHttpHost())) {
          $element[$delta]['#url'] = JOINUP_VIDEO_EMBED_COOKIE_URL . urlencode($element[$delta]['#url']);
        }

        $element[$delta] = [
          '#type' => 'container',
          '#attributes' => ['class' => [Html::cleanCssIdentifier(sprintf('video-embed-field-provider-%s', $provider->getPluginId()))]],
          'children' => $element[$delta],
        ];

        // For responsive videos, wrap each field item in it's own container.
        if ($this->getSetting('responsive')) {
          $element[$delta]['#attached']['library'][] = 'video_embed_field/responsive-video';
          $element[$delta]['#attributes']['class'][] = 'video-embed-field-responsive-video';
        }
      }

    }
    return $element;
  }

}
