<?php

namespace Drupal\collection\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block containing a form that allows to join a collection.
 *
 * @Block(
 *  id = "join_collection_block",
 *  admin_label = @Translation("Join collection block"),
 * )
 */
class JoinCollectionBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The collection to join.
   *
   * @var \Drupal\rdf_entity\RdfInterface
   */
  protected $collection;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface $user
   */
  protected $user;

  /**
   * The context provider for the Collection context.
   *
   * @var \Drupal\Core\Plugin\Context\ContextProviderInterface
   */
  protected $contextProvider;

  /**
   * Constructs a JoinCollectionBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountProxyInterface $user
   *   The current user.
   * @param \Drupal\Core\Plugin\Context\ContextProviderInterface $context_provider
   *   The context provider for the Collection context.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $user, ContextProviderInterface $context_provider) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->user = $user;
    $this->contextProvider = $context_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('collection.collection_route_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Display the Join Collection form.
    $collection = $this->contextProvider->getRuntimeContexts(['og'])['og']->getContextValue();
    return \Drupal::formBuilder()->getForm('\Drupal\collection\Form\JoinCollectionForm', $this->user, $collection);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // This block varies per user.
    $contexts = parent::getCacheContexts();
    return Cache::mergeContexts($contexts, ['user']);
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    // Since the visibility of this block depends on whether or not the user is
    // a member of the collection we need to delegate the access checking to the
    // form. Otherwise the first time a user would join a collection this form
    // would not be shown on the page, and its cache tags would not bubble up
    // any more, causing the block to disappear for all users.
    return AccessResult::allowed();
  }

}
