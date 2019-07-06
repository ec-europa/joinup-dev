<?php

namespace Drupal\joinup_cck\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\video_embed_field\Plugin\Field\FieldFormatter\Video as OriginalVideo;

/**
 * Plugin implementation of the video field formatter that uses the joinup cck.
 */
class Video extends OriginalVideo {

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
        $element[$delta]['#url'] = JOINUP_CCK_EMBED_COOKIE_URL . urlencode($element[$delta]['#url']);

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
