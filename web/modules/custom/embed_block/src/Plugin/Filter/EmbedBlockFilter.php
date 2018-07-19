<?php

namespace Drupal\embed_block\Plugin\Filter;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
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
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

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
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BlockManagerInterface $block_plugin_manager, RendererInterface $renderer, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->blockPluginManager = $block_plugin_manager;
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
      $container->get('plugin.manager.block'),
      $container->get('renderer'),
      $container->get('current_user')
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
          if ($block_plugin->access($this->currentUser)) {
            $build = $block_plugin->build();
            $block_content = $this->renderer->render($build);
          }
          // If the user cannot access the block, means that the block exists
          // but should not be rendered, so we still have to replace the
          // placeholder with an empty string.
          else {
            $block_content = '';
          }

          // Replace the placeholder.
          $text = str_replace($found[0], $block_content, $text);

          // Cache metadata applies regardless if the user can access the block.
          $response->addCacheableDependency($block_plugin);
        }
        catch (PluginException $exception) {
          // The plugin doesn't exist, we don't touch the placeholder.
        }
        $processed[$found['plugin_id']] = TRUE;
      }
    }

    return $response->setProcessedText($text);
  }

}
