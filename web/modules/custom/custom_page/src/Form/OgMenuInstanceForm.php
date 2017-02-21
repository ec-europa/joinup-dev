<?php

namespace Drupal\custom_page\Form;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Render\Element;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\node\NodeInterface;
use Drupal\og\OgAccessInterface;
use Drupal\og_menu\Form\OgMenuInstanceForm as OriginalOgMenuInstanceForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

/**
 * Customized form controller for OG Menu instance edit forms.
 *
 * This simplifies the standard menu edit form from OG Menu for the navigation
 * menus of collections. The standard form is intended for webmasters, but the
 * navigation menu is managed by non-technical collection facilitators in the
 * frontend.
 *
 * The following changes are made:
 * - The wording is updated, it mentions 'custom pages' instead of 'menu links'.
 * - The links to the forms of the menu items have been removed. The menu items
 *   are managed automatically whenever a custom page is added to a collection
 *   or removed. Facilitators or moderators should not have access to it.
 * - The operations that originally deal with menu items, are now dealing
 *   directly with custom pages.
 *
 * Most of this code is copied directly from the original form class.
 *
 * @see \Drupal\og_menu\Form\OgMenuInstanceForm
 *
 * @ingroup custom_page
 */
class OgMenuInstanceForm extends OriginalOgMenuInstanceForm {

  /**
   * The Symfony route matcher.
   *
   * @var \Symfony\Component\Routing\Matcher\UrlMatcherInterface
   */
  protected $urlMatcher;

  /**
   * Constructs a MenuForm object.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query_factory
   *   The factory for entity queries.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link manager.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_tree
   *   The menu tree service.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG Access service.
   * @param \Symfony\Component\Routing\Matcher\UrlMatcherInterface $url_matcher
   *   The Symfony route matcher.
   */
  public function __construct(EntityManagerInterface $entity_manager, QueryFactory $entity_query_factory, MenuLinkManagerInterface $menu_link_manager, MenuLinkTreeInterface $menu_tree, LinkGeneratorInterface $link_generator, OgAccessInterface $og_access, UrlMatcherInterface $url_matcher) {
    parent::__construct($entity_manager, $entity_query_factory, $menu_link_manager, $menu_tree, $link_generator, $og_access);

    $this->urlMatcher = $url_matcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity.query'),
      $container->get('plugin.manager.menu.link'),
      $container->get('menu.link_tree'),
      $container->get('link_generator'),
      $container->get('og.access'),
      $container->get('router.no_access_checks')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // If we are not showing the navigation menu for custom pages, fall back to
    // the original form.
    if ($this->entity->getType() !== 'navigation') {
      return parent::buildForm($form, $form_state);
    }

    $form = EntityForm::buildForm($form, $form_state);
    // On entity add, no links are attached yet, so bail out here.
    if ($this->entity->isNew()) {
      return $form;
    }

    // Ensure that menu_overview_form_submit() knows the parents of this form
    // section.
    if (!$form_state->has('menu_overview_form_parents')) {
      $form_state->set('menu_overview_form_parents', []);
    }

    $form['#attached']['library'][] = 'menu_ui/drupal.menu_ui.adminforms';

    $tree = $this->menuTree->load('ogmenu-' . $this->entity->id(), new MenuTreeParameters());

    // We indicate that a menu administrator is running the menu access check.
    $this->getRequest()->attributes->set('_menu_admin', TRUE);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuTree->transform($tree, $manipulators);
    $this->getRequest()->attributes->set('_menu_admin', FALSE);

    // Determine the delta; the number of weights to be made available.
    $count = function (array $tree) {
      $sum = function ($carry, MenuLinkTreeElement $item) {
        return $carry + $item->count();
      };
      return array_reduce($tree, $sum);
    };
    $delta = max($count($tree), 50);

    $form['links'] = [
      '#type' => 'table',
      '#theme' => 'table__menu_overview',
      '#header' => [
        $this->t('Page'),
        [
          'data' => $this->t('Enabled'),
          'class' => ['checkbox'],
        ],
        $this->t('Weight'),
        [
          'data' => $this->t('Operations'),
          'colspan' => 3,
        ],
      ],
      '#attributes' => [
        'id' => 'menu-overview',
      ],
      '#tabledrag' => [
        [
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'menu-parent',
          'subgroup' => 'menu-parent',
          'source' => 'menu-id',
          'hidden' => TRUE,
          'limit' => \Drupal::menuTree()->maxDepth() - 1,
        ],
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'menu-weight',
        ],
      ],
    ];

    $form['links']['#empty'] = $this->t('There are no custom pages yet.');

    $links = $this->buildOverviewTreeForm($tree, $delta);
    foreach (Element::children($links) as $id) {
      if (isset($links[$id]['#item'])) {
        $element = $links[$id];

        $form['links'][$id]['#item'] = $element['#item'];

        // TableDrag: Mark the table row as draggable.
        $form['links'][$id]['#attributes'] = $element['#attributes'];
        $form['links'][$id]['#attributes']['class'][] = 'draggable';

        // TableDrag: Sort the table row according to its existing/configured
        // weight.
        $form['links'][$id]['#weight'] = $element['#item']->link->getWeight();

        // Add special classes to be used for tabledrag.js.
        $element['parent']['#attributes']['class'] = ['menu-parent'];
        $element['weight']['#attributes']['class'] = ['menu-weight'];
        $element['id']['#attributes']['class'] = ['menu-id'];

        $form['links'][$id]['title'] = [
          [
            '#theme' => 'indentation',
            '#size' => $element['#item']->depth - 1,
          ],
          $element['title'],
        ];
        $form['links'][$id]['enabled'] = $element['enabled'];
        $form['links'][$id]['enabled']['#wrapper_attributes']['class'] = ['checkbox', 'menu-enabled'];

        $form['links'][$id]['weight'] = $element['weight'];

        // Operations (dropbutton) column.
        $form['links'][$id]['operations'] = $element['operations'];

        $form['links'][$id]['id'] = $element['id'];
        $form['links'][$id]['parent'] = $element['parent'];
      }
    }

    $form['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Drag pages to change their order. Disable pages to hide them from the menu.'),
      '#attributes' => ['class' => ['description']],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildOverviewTreeForm($tree, $delta) {
    // If we are not showing the navigation menu for custom pages, fall back to
    // the original method.
    if ($this->entity->getType() !== 'navigation') {
      return parent::buildOverviewTreeForm($tree, $delta);
    }

    $form = &$this->overviewTreeForm;
    $tree_access_cacheability = new CacheableMetadata();
    foreach ($tree as $element) {
      $tree_access_cacheability = $tree_access_cacheability->merge(CacheableMetadata::createFromObject($element->access));

      // Only render accessible links.
      if (!$element->access->isAllowed()) {
        continue;
      }

      /** @var \Drupal\Core\Menu\MenuLinkInterface $link */
      $link = $element->link;
      if ($link) {
        $id = 'menu_plugin_id:' . $link->getPluginId();
        $form[$id]['#item'] = $element;
        $form[$id]['#attributes'] = $link->isEnabled() ? ['class' => ['menu-enabled']] : ['class' => ['menu-disabled']];
        $form[$id]['title'] = Link::fromTextAndUrl($link->getTitle(), $link->getUrlObject())->toRenderable();

        $form[$id]['enabled'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Enable @title menu link', ['@title' => $link->getTitle()]),
          '#title_display' => 'invisible',
          '#default_value' => $link->isEnabled(),
        ];
        $form[$id]['weight'] = [
          '#type' => 'weight',
          '#delta' => $delta,
          '#default_value' => $link->getWeight(),
          '#title' => $this->t('Weight for @title', ['@title' => $link->getTitle()]),
          '#title_display' => 'invisible',
        ];
        $form[$id]['id'] = [
          '#type' => 'hidden',
          '#value' => $link->getPluginId(),
        ];
        $form[$id]['parent'] = [
          '#type' => 'hidden',
          '#default_value' => $link->getParent(),
        ];

        // Build a list of operations. This form is shown to users that do not
        // have access to edit menu links, so instead we are showing links to
        // edit the custom pages directly.
        $operations = [];

        // Skip this if this link is not pointing to the canonical view of a
        // custom page, since this means the link has been added manually
        // somehow, probably by an administrator.
        $route_info = $this->urlMatcher->match($link->getUrlObject()->toString());
        if (
          // The link should be to a canonical path of a node.
          $route_info['_route'] === 'entity.node.canonical'
          // The node should be resolved.
          && !empty($route_info['node'])
          && ($node = $route_info['node'])
          // It should be a node too, one can never be too careful.
          && $node instanceof NodeInterface
          // It should be a custom page.
          && $node->bundle() === 'custom_page'
        ) {
          $operations['edit'] = [
            'title' => $this->t('Edit'),
            'url' => $node->toUrl('edit-form'),
            // Bring the user back to the menu overview.
            'query' => $this->getDestinationArray(),
          ];
          $operations['delete'] = [
            'title' => $this->t('Delete'),
            'url' => $node->toUrl('delete-form'),
            'query' => $this->getDestinationArray(),
          ];
        }

        // Only display the operations to which the user has access.
        foreach ($operations as $key => $operation) {
          if (!$operation['url']->access()) {
            unset($operations[$key]);
          }
        }

        $form[$id]['operations'] = [
          '#type' => 'operations',
          '#links' => $operations,
        ];
      }

      if ($element->subtree) {
        $this->buildOverviewTreeForm($element->subtree, $delta);
      }
    }

    $tree_access_cacheability
      ->merge(CacheableMetadata::createFromRenderArray($form))
      ->applyTo($form);

    return $form;
  }

}
