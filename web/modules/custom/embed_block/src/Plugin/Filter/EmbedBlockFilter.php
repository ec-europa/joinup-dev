<?php

namespace Drupal\embed_block\Plugin\Filter;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Embeds blocks into content.
 *
 * @Filter(
 *   id = "embed_block",
 *   title = @Translation("Embed Block"),
 *   description = @Translation("Allows to place blocks into content."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class EmbedBlockFilter extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The block plugin manager service.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockPluginManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Creates a new filter class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Block\BlockManagerInterface $block_plugin_manager
   *   The block plugin manager service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BlockManagerInterface $block_plugin_manager, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->blockPluginManager = $block_plugin_manager;
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
      $container->get('plugin.manager.block'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $response = new FilterProcessResult();

    preg_match_all('/{block:(?<plugin_id>[^}].*)}/', $text, $match, PREG_SET_ORDER);

    $processed = [];
    foreach ($match as $found) {
      // A block could occur multiple times. We optimize the number of
      // replacements by keeping track on replacements already made.
      if (!isset($processed[$found['plugin_id']])) {
        try {
          /** @var \Drupal\Core\Block\BlockPluginInterface $block_plugin */
          $block_plugin = $this->blockPluginManager->createInstance($found[1]);
          $build = $block_plugin->build();
          $block_content = $this->renderer->render($build);
          $response
            ->setCacheTags($block_plugin->getCacheTags())
            ->setCacheContexts($block_plugin->getCacheContexts())
            ->setCacheMaxAge($block_plugin->getCacheMaxAge());
        }
        catch (\Exception $exception) {
          $block_content = '';
        }
        $text = str_replace($found[0], $block_content, $text);
        $processed[$found['plugin_id']] = TRUE;
      }
    }

    return $response->setProcessedText($text);
  }

}
