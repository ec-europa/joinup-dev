<?php

declare(strict_types = 1);

namespace Drupal\joinup_menu;

use Drupal\collection\Entity\CollectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Url;
use Drupal\joinup_collection\JoinupCollectionHelper;
use Exception;

/**
 * Helper methods for working with menus.
 */
class MenuHelper implements MenuHelperInterface {

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected LoggerChannelFactoryInterface $logger;

  /**
   * The menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected MenuLinkTreeInterface $menuLinkTree;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs an instance of the MenuHelper service.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger channel factory.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_link_tree
   *   The menu link tree service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(LoggerChannelFactoryInterface $logger, MenuLinkTreeInterface $menu_link_tree, EntityTypeManagerInterface $entity_type_manager) {
    $this->logger = $logger;
    $this->menuLinkTree = $menu_link_tree;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getBclAccountMenu(): array {
    $tree = $this->menuLinkTree->load('account', new MenuTreeParameters());
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuLinkTree->transform($tree, $manipulators);
    $tree = array_filter($tree, function (MenuLinkTreeElement $element): bool {
      // Hide inaccessible links, as well as the 'EU Login info' link. This link
      // is only intended for anonymous users.
      // @see joinup_eulogin_preprocess_menu()
      return $element->link->isEnabled() && $element->link->getPluginId() !== 'joinup_eulogin.eu_login_info';
    });

    return array_map(function (MenuLinkTreeElement $element): array {
      return [
        'link' => $element->link->getUrlObject(),
        'label' => $element->link->getTitle(),
      ];
    }, $tree);
  }

  /**
   * {@inheritdoc}
   */
  public function getBclAnonymousMenu(): array {
    // Populate the links shown to anonymous users.
    $anonymous_links = [];

    // Add a link to the homepage, only to be shown in the hamburger menu.
    $anonymous_links[] = [
      'label' => t('Home'),
      'link' => Url::fromRoute('<front>'),
      'hide_on_desktop' => TRUE,
    ];

    // Generate a link to EU Login.
    $cas_login_link = Url::fromRoute('cas.login');
    $anonymous_links[] = [
      'label' => t('Sign in'),
      'link' => $cas_login_link,
    ];
    $variables['anonymous_links'] = $anonymous_links;

    // Generate a link button that opens the "Get started" popover.
    $popover = [
      'cta' => [
        // Even though the link claims to lead to an account creation form, we are
        // linking to the EU Login portal. Account creation is not handled inside
        // the Joinup domain.
        'link' => $cas_login_link,
        'attributes' => 'tabindex="0"',
        'text' => t('Create an account'),
      ],
      'description' => t('As a signed-in user you can create content, become a member of a community, receive notifications on your favourite solutions and topics, and access all other features available on the platform.'),
    ];
    $anonymous_links[] = [
      'label' => t('Get started'),
      'popover' => $popover,
    ];

    // Generate a link to the About page of the Joinup collection, to show as the
    // "About us" link in the hamburger menu for anonymous users.
    $rdf_storage = $this->entityTypeManager->getStorage('rdf_entity');
    try {
      $joinup_collection = $rdf_storage->load(JoinupCollectionHelper::getCollectionId());
      if (!$joinup_collection instanceof CollectionInterface) {
        throw new Exception();
      }
      $url = $joinup_collection->toUrl('about-page');
      $anonymous_links[] = [
        'label' => t('About us'),
        'link' => $url,
        'hide_on_desktop' => TRUE,
      ];
    }
    catch (Exception $e) {
      // The Joinup collection could not be loaded or the link to the About page
      // could not be generated. This should not crash the page since the
      // collection is considered to be optional. It is for example possible for a
      // project to reuse the Joinup open source code without hosting a "Joinup"
      // collection.
      // However in scope of the Ventuno theme the About Us link is considered
      // to be an important element, so we log a warning to alert the webmaster
      // that corrective action is required.
      $this->logger->get('joinup_menu')->warning('"About us" link could not be rendered because the Joinup collection is not defined or doesn\'t have an About page.');
    }

    return $anonymous_links;
  }

}
