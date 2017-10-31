<?php

namespace Drupal\joinup_core\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\custom_page\CustomPageOgMenuLinksManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\UriEncoder;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a VBO action that changes the group for nodes.
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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SelectionPluginManagerInterface $selection_plugin_manager, Renderer $renderer, CustomPageOgMenuLinksManagerInterface $custom_page_og_links_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->selectionPluginManager = $selection_plugin_manager;
    $this->renderer = $renderer;
    $this->customPageOgMenuLinksManager = $custom_page_og_links_manager;
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
      $container->get('custom_page.og_menu_links_manager')
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
    $uri = 'internal:/' . trim($form_state->get('views_bulk_operations')['redirect_uri']['destination'], '/');
    $url = Url::fromUri($uri);
    if ($url->isRouted() && ($parameters = $url->getRouteParameters()) && !empty($parameters['rdf_entity'])) {
      /** @var \Drupal\rdf_entity\RdfInterface $source_entity */
      $source_entity = Rdf::load(UriEncoder::decodeUrl($parameters['rdf_entity']));
      if ($source_entity->id() === $form_state->getValue('group')) {
        $form_state->setErrorByName('group', $this->t("The destination group is the same as the source group: %group. Please, select other destination group.", [
          '%group' => $source_entity->label(),
        ]));
        return;
      }
    }
    else {
      throw new \RuntimeException("The view bulk operation has been triggered from an invalid page.");
    }
  }

}
