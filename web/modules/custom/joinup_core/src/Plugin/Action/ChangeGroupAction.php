<?php

declare(strict_types = 1);

namespace Drupal\joinup_core\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Session\AccountInterface;
use Drupal\custom_page\CustomPageOgMenuLinksManagerInterface;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\node\NodeInterface;
use Drupal\og_menu\OgMenuInstanceInterface;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\sparql_entity_storage\UriEncoder;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a VBO action that changes the group for nodes.
 *
 * This will also move the node into the OG Menu for the 'navigation' menu.
 *
 * @Action(
 *   id = "joinup_change_group",
 *   label = @Translation("Move to other group"),
 *   type = "node",
 *   confirm = FALSE,
 *   pass_context = TRUE,
 *   pass_view = FALSE,
 * )
 */
class ChangeGroupAction extends ViewsBulkOperationsActionBase implements ContainerFactoryPluginInterface {

  /**
   * The selection plugin manager service.
   *
   * @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface
   */
  protected $selectionPluginManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * The custom pages OG menu links manager service.
   *
   * @var \Drupal\custom_page\CustomPageOgMenuLinksManagerInterface
   */
  protected $customPageOgMenuLinksManager;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The corresponding OG menu instance.
   *
   * @var \Drupal\og_menu\OgMenuInstanceInterface
   */
  protected $ogMenuInstance;

  /**
   * A list UUIDs of menu links of top-level custom pages.
   *
   * @var string[]
   */
  protected $topLevelCustomPages = [];

  /**
   * A list of menu links entities, keyed by custom page node ID.
   *
   * @var \Drupal\menu_link_content\MenuLinkContentInterface
   */
  protected $pageMenuLinks = [];

  /**
   * Constructs a new 'joinup_change_group' action plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $selection_plugin_manager
   *   The selection plugin manager service.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer service.
   * @param \Drupal\custom_page\CustomPageOgMenuLinksManagerInterface $custom_page_og_links_manager
   *   The custom pages OG menu links manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SelectionPluginManagerInterface $selection_plugin_manager, Renderer $renderer, CustomPageOgMenuLinksManagerInterface $custom_page_og_links_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->selectionPluginManager = $selection_plugin_manager;
    $this->renderer = $renderer;
    $this->customPageOgMenuLinksManager = $custom_page_og_links_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.entity_reference_selection'),
      $container->get('renderer'),
      $container->get('custom_page.og_menu_links_manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access($node, AccountInterface $account = NULL, $return_as_object = FALSE) {
    // The access is limited at the view level.
    return $return_as_object ? AccessResult::allowed() : TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $nodes) {
    $message = array_fill_keys(['error', 'warning', 'status'], []);
    $group_id = $this->getConfiguration()['group'];
    if (!$group = Rdf::load($group_id)) {
      throw new \RuntimeException("Cannot load RDF entity with ID $group_id");
    }

    // Order the nodes, so that the custom page parent nodes are first. This is
    // important in order to check if the parent of the 2nd tier custom pages
    // were moved too.
    $this->reorder($nodes);

    $initial_nids = array_map(function (NodeInterface $node) {
      return $node->id();
    }, $nodes);
    /** @var \Drupal\node\NodeInterface[] $nodes */
    while ($nodes) {
      $node = array_shift($nodes);
      $is_custom_page = $node->bundle() === 'custom_page';
      // Custom pages cannot be group content in solutions.
      if ($group->bundle() === 'solution' && $is_custom_page) {
        $args = ['%title' => $node->label()];
        $message['warning'][] = $this->t("Group of %title cannot be changed because a custom page cannot be be attached to a solution.", $args);
        continue;
      }

      // It might be a parent custom page.
      if ($is_custom_page && $children = $this->customPageOgMenuLinksManager->getChildren($node)) {
        // Filter out child nodes that are already in the main set.
        $children = array_diff_key($children, array_flip($initial_nids));
        array_walk($children, function (NodeInterface $child) {
          // Flag this as a child.
          $child->isChild = TRUE;
        });
        // Add children in the flow, just after the parent which is currently
        // being processed.
        $nodes = array_merge($children, $nodes);
      }

      $args = [
        '@title' => $node->label(),
        ':url' => $node->toUrl()->toString(),
        '@type' => $node->type->entity->label(),
        '@group' => $group->label(),
        ':group_url' => $group->toUrl()->toString(),
      ];
      try {
        // Prevent notification dispatching.
        // @see joinup_notification_dispatch_notification()
        $node->skip_notification = TRUE;
        $node->set('og_audience', $group_id)->save();
        if (empty($node->isChild)) {
          $message['status'][] = [['#markup' => $this->t('@type <a href=":url">@title</a> group was changed to <a href=":group_url">@group</a>.', $args)]];
        }
        else {
          $last_message =& $message['status'][count($message['status']) - 1];
          if (!isset($last_message[1])) {
            $last_message[1] = [
              '#theme' => 'item_list',
              '#items' => [],
            ];
          }
          $last_message[1]['#items'][] = $this->t('Child @type <a href=":url">@title</a> group was changed too.', $args);
        }

        // Check the user selected 2nd tier custom pages.
        if ($is_custom_page && empty($node->isChild)) {
          if ($parent_id = $this->pageMenuLinks[$node->id()]->getParentId()) {
            list(, $uuid) = explode(':', $parent_id, 2);
            // If the custom page has a parent that wasn't selected by the user,
            // the page will be moved as top-level page. This is accomplished by
            // removing the corresponding OG menu link parent reference.
            if (!in_array($uuid, $this->topLevelCustomPages)) {
              $this->pageMenuLinks[$node->id()]->set('parent', NULL)->save();
            }
          }
        }
      }
      catch (\Exception $e) {
        $message['error'][] = $this->t("Error while trying to change the group for '@title'.", $args);
      }
    }

    foreach ($message as $type => $message_group) {
      if ($message_group) {
        $list = [
          '#theme' => 'item_list',
          '#items' => $message_group,
        ];
        drupal_set_message($this->renderer->render($list), $type);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function execute(NodeInterface $node = NULL) {
    $this->executeMultiple([$node]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginBase $selection */
    $selection = $this->selectionPluginManager->createInstance('default:rdf_entity', [
      'target_type' => 'rdf_entity',
      'target_bundles' => ['collection', 'solution'],
    ]);
    $selection_settings = $selection->getConfiguration() + [
      'match_operator' => 'CONTAINS',
    ];

    $form['group'] = [
      '#title' => $this->t('Select the destination collection or solution'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'rdf_entity',
      '#selection_handler' => 'default:rdf_entity',
      '#selection_settings' => $selection_settings,
      '#maxlength' => 2048,
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Url $url */
    $url = $form_state->get('views_bulk_operations')['redirect_url'];
    if ($url->isRouted() && ($parameters = $url->getRouteParameters()) && !empty($parameters['rdf_entity'])) {
      /** @var \Drupal\rdf_entity\RdfInterface $source_entity */
      $source_entity = Rdf::load(UriEncoder::decodeUrl($parameters['rdf_entity']));
      if ($source_entity->id() === $form_state->getValue('group')) {
        $form_state->setErrorByName('group', $this->t("The destination group is the same as the source group: %group. Please, select other destination group.", [
          '%group' => $source_entity->label(),
        ]));
        return;
      }
      $form_state->setValue('source_entity', $source_entity);
    }
    else {
      throw new \RuntimeException("The view bulk operation has been triggered from an invalid page.");
    }
  }

  /**
   * Order the nodes, so that the custom page parent nodes are first.
   *
   * This is important in order to check if the parent of the 2nd tier custom
   * pages were moved too. While we are iterating over all nodes, we are also
   * collecting the UUIDs of 1st tier custom page OG menu links so that we can
   * check later if each of the 2nd tier custom pages are linked to one of these
   * parents. In this iteration we are also caching the relation between the
   * custom page nodes and their corresponding OG menu link.
   *
   * @param \Drupal\node\NodeInterface[] $nodes
   *   Nodes to be re-ordered.
   */
  protected function reorder(array &$nodes) {
    $reordered = $others = [];
    while ($nodes) {
      $node = array_shift($nodes);
      $nid = $node->id();
      if ($node->bundle() === 'custom_page') {
        /** @var \Drupal\menu_link_content\MenuLinkContentInterface $menu_link */
        if ($menu_link = $this->getOgMenuLink($node)) {
          if (!$menu_link->getParentId()) {
            // It's a top custom page.
            $this->topLevelCustomPages[] = $menu_link->uuid();
            $reordered[$nid] = $node;
          }
          $this->pageMenuLinks[$nid] = $menu_link;
        }
      }
      if (!isset($reordered[$nid])) {
        $others[$nid] = $node;
      }
    }
    // Re-order the list.
    $nodes = array_merge($reordered, $others);
  }

  /**
   * Gets the OG menu link given a custom page.
   *
   * @param \Drupal\node\NodeInterface $custom_page
   *   The custom page node.
   *
   * @return \Drupal\menu_link_content\MenuLinkContentInterface|null
   *   The OG menu link.
   */
  protected function getOgMenuLink(NodeInterface $custom_page) : ?MenuLinkContentInterface {
    $menu_instance = $this->getOgMenuInstance();
    if (empty($menu_instance)) {
      return NULL;
    }
    $menu_link_storage = $this->entityTypeManager->getStorage('menu_link_content');
    $uri = 'entity:node/' . $custom_page->id();
    $mids = $menu_link_storage->getQuery()
      ->condition('bundle', 'menu_link_content')
      ->condition('menu_name', "ogmenu-{$menu_instance->id()}")
      ->condition('link.uri', $uri)
      ->execute();
    if ($mids) {
      $mid = reset($mids);
      return $menu_link_storage->load($mid);
    }
    return NULL;
  }

  /**
   * Gets the OG menu instance corresponding to the actual source entity.
   *
   * @return \Drupal\og_menu\OgMenuInstanceInterface|null
   *   The OG menu instance.
   */
  protected function getOgMenuInstance() : ?OgMenuInstanceInterface {
    if (!isset($this->ogMenuInstance)) {
      $og_menu_storage = $this->entityTypeManager->getStorage('ogmenu_instance');
      // @todo If we ever support more than one OG Menu then we should no longer
      //   hardcode on the 'navigation' menu but loop over all available menus.
      $og_menu_instances = $og_menu_storage->loadByProperties([
        'type' => 'navigation',
        'og_audience' => $this->configuration['source_entity']->id(),
      ]);
      $this->ogMenuInstance = $og_menu_instances ? reset($og_menu_instances) : NULL;
    }
    return $this->ogMenuInstance;
  }

}
