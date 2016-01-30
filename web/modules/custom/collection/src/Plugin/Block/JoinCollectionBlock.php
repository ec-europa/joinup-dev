<?php

/**
 * @file
 * Contains \Drupal\collection\Plugin\Block\JoinCollectionBlock.
 */

namespace Drupal\collection\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
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
   * @var \Drupal\collection\CollectionInterface
   */
  protected $collection;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface,
   */
  protected $currentRouteMatch;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface $user
   */
  protected $user;

  /**
   * Constructs a JoinCollectionBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The current route match service.
   * @param \Drupal\Core\Session\AccountProxyInterface $user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $current_route_match, AccountProxyInterface $user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentRouteMatch = $current_route_match;
    $this->user = $user;
    // Retrieve the collection from the route.
    $this->collection = $this->currentRouteMatch->getParameter('collection');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if (empty($this->collection)) {
      throw new \Exception('The "Join Collection" block can only be shown on collection pages.');
    }

    // Display the Join Collection form.
    return \Drupal::formBuilder()->getForm('\Drupal\collection\Form\JoinCollectionForm', $this->user, $this->collection);
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