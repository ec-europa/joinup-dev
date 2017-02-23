<?php

namespace Drupal\custom_page\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\joinup_core\JoinupRelationManager;
use Drupal\og_menu\Entity\OgMenuInstance;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'MenuSubPages' block.
 *
 * @Block(
 *  id = "menu_sub_pages",
 *  admin_label = @Translation("Menu sub pages"),
 * )
 */
class MenuSubPages extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\joinup_core\JoinupRelationManager definition.
   *
   * @var \Drupal\joinup_core\JoinupRelationManager
   */
  protected $joinupCoreRelationsManager;

  /**
   * The collection route context service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextProviderInterface
   */
  protected $collectionContext;

  /**
   * Construct.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\joinup_core\JoinupRelationManager $joinup_core_relations_manager
   *   The joinup relation manager service.
   * @param \Drupal\Core\Plugin\Context\ContextProviderInterface $collection_context
   *   The collection context.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, JoinupRelationManager $joinup_core_relations_manager, ContextProviderInterface $collection_context) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->joinupCoreRelationsManager = $joinup_core_relations_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('joinup_core.relations_manager'),
      $container->get('collection.collection_route_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['menu_sub_pages']['#markup'] = 'Implement MenuSubPages.';

    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * Checks for the permission based on whether the page is a custom page and
   * the custom page is assigned to the og menu and the custom page is in the
   * root level of the menu.
   */
  protected function blockAccess(AccountInterface $account) {
    $collection_contexts = $this->collectionContext->getRuntimeContexts(['og']);
    if ($collection_contexts && $collection_contexts['og']->hasContextValue()) {
      $parent = $collection_contexts['og']->getContextValue();
      /** @var \Drupal\og\MembershipManagerInterface $membership_manager */
      $membership_manager = \Drupal::service('og.membership_manager');
      $results = $membership_manager->getGroupContentIds($parent, ['og_menu']);
      $og_menu_instance_id = reset($results);
      $og_menu_instance = OgMenuInstance::load($og_menu_instance_id);

      return parent::blockAccess($account);
    }
  }

}
