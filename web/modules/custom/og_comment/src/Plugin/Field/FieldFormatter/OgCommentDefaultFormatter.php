<?php

namespace Drupal\og_comment\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\comment\CommentInterface;
use Drupal\comment\CommentManagerInterface;
use Drupal\comment\Plugin\Field\FieldFormatter\CommentDefaultFormatter;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\og\GroupTypeManager;
use Drupal\og\MembershipManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Overrides the default comment formatter.
 */
class OgCommentDefaultFormatter extends CommentDefaultFormatter {

  /**
   * A list of og group entities.
   *
   * @var \Drupal\Core\Entity\EntityInterface[]
   */
  protected $groups;

  /**
   * Active database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The og group type manager.
   *
   * @var \Drupal\og\GroupTypeManager
   */
  protected $groupTypeManager;

  /**
   * The og membership manager.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity.manager'),
      $container->get('entity.form_builder'),
      $container->get('current_route_match'),
      $container->get('database'),
      $container->get('og.group_type_manager'),
      $container->get('og.membership_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, EntityManagerInterface $entity_manager, EntityFormBuilderInterface $entity_form_builder, RouteMatchInterface $route_match, Connection $database, GroupTypeManager $group_type_manager, MembershipManagerInterface $membership_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $current_user, $entity_manager, $entity_form_builder, $route_match);
    $this->database = $database;
    $this->groupTypeManager = $group_type_manager;
    $this->membershipManager = $membership_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    /** @var \Drupal\comment\CommentFieldItemList $items */
    $entity = $items->getEntity();

    // Retrieve the parent entities.
    if ($this->groupTypeManager->isGroup($entity->getEntityTypeId(), $entity->bundle())) {
      $this->groups = [$entity];
    }
    if ($this->groupTypeManager->isGroupContent($entity->getEntityTypeId(), $entity->bundle())) {
      $this->groups = $this->membershipManager->getGroups($entity);
    }
    // If $groups is empty, fallback to the global rendering.
    if (empty($this->groups)) {
      return parent::viewElements($items, $langcode);
    }

    $elements = [];
    $output = [];

    $field_name = $this->fieldDefinition->getName();
    $status = $items->status;

    if ($status != CommentItemInterface::HIDDEN && empty($entity->in_preview) &&
      // Comments are added to the search results and search index by
      // comment_node_update_index() instead of by this formatter, so don't
      // return anything if the view mode is search_index or search_result.
      !in_array($this->viewMode, ['search_result', 'search_index'])) {
      $comment_settings = $this->getFieldSettings();

      // Only attempt to render comments if the entity has visible comments.
      // Unpublished comments are not included in
      // $entity->get($field_name)->comment_count, but unpublished comments
      // should display if the user is an administrator.
      $elements['#cache']['contexts'][] = 'user.permissions';
      if ($items->access('access comments') || $items->access('administer comments')) {
        $output['comments'] = [];

        if ($entity->get($field_name)->comment_count || $items->access('administer comments')) {
          $mode = $comment_settings['default_mode'];
          $comments_per_page = $comment_settings['per_page'];
          $comments = $this->loadThread($items, $entity, $field_name, $mode, $comments_per_page, $this->getSetting('pager_id'));
          if ($comments) {
            $build = $this->viewBuilder->viewMultiple($comments, $this->getSetting('view_mode'));
            $build['pager']['#type'] = 'pager';
            // CommentController::commentPermalink() calculates the page number
            // where a specific comment appears and does a subrequest pointing
            // to that page, we need to pass that subrequest route to our pager
            // to keep the pager working.
            $build['pager']['#route_name'] = $this->routeMatch->getRouteObject();
            $build['pager']['#route_parameters'] = $this->routeMatch->getRawParameters()->all();
            if ($this->getSetting('pager_id')) {
              $build['pager']['#element'] = $this->getSetting('pager_id');
            }
            $output['comments'] += $build;
          }
        }
      }

      // Append comment form if the comments are open and the form is set to
      // display below the entity. Do not show the form for the print view mode.
      if ($status == CommentItemInterface::OPEN && $comment_settings['form_location'] == CommentItemInterface::FORM_BELOW && $this->viewMode != 'print') {
        // Only show the add comment form if the user has permission.
        if ($items->access('post comments')) {
          $output['comment_form'] = [
            '#lazy_builder' => ['comment.lazy_builders:renderForm', [
              $entity->getEntityTypeId(),
              $entity->id(),
              $field_name,
              $this->getFieldSetting('comment_type'),
            ],
            ],
            '#create_placeholder' => TRUE,
          ];
        }
      }

      $elements[] = $output + [
        '#comment_type' => $this->getFieldSetting('comment_type'),
        '#comment_display_mode' => $this->getFieldSetting('default_mode'),
        'comments' => [],
        'comment_form' => [],
      ];
    }

    return $elements;
  }

  /**
   * Build a list of comments.
   *
   * This is forked of CommentStorage::loadThread.
   * The difference is in the calls to hasPermission(), which needs to
   * be checked against the og permissions.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values to be rendered.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity whose comment(s) needs rendering.
   * @param string $field_name
   *   The field_name whose comment(s) needs rendering.
   * @param int $mode
   *   The comment display mode: CommentManagerInterface::COMMENT_MODE_FLAT or
   *   CommentManagerInterface::COMMENT_MODE_THREADED.
   * @param int $comments_per_page
   *   (optional) The amount of comments to display per page.
   *   Defaults to 0, which means show all comments.
   * @param int $pager_id
   *   (optional) Pager id to use in case of multiple pagers on the one page.
   *   Defaults to 0; is only used when $comments_per_page is greater than zero.
   *
   * @see \Drupal\comment\CommentStorage::loadThread()
   *
   * @return array
   *   Ordered array of comment objects, keyed by comment id.
   */
  public function loadThread(FieldItemListInterface $items, EntityInterface $entity, $field_name, $mode, $comments_per_page = 0, $pager_id = 0) {
    $query = $this->database->select('comment_field_data', 'c');
    $query->addField('c', 'cid');
    $query
      ->condition('c.entity_id', $entity->id())
      ->condition('c.entity_type', $entity->getEntityTypeId())
      ->condition('c.field_name', $field_name)
      ->condition('c.default_langcode', 1)
      ->addTag('entity_access')
      ->addTag('comment_filter')
      ->addMetaData('base_table', 'comment')
      ->addMetaData('entity', $entity)
      ->addMetaData('field_name', $field_name);

    if ($comments_per_page) {
      $query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')
        ->limit($comments_per_page);
      if ($pager_id) {
        $query->element($pager_id);
      }

      $count_query = $this->database->select('comment_field_data', 'c');
      $count_query->addExpression('COUNT(*)');
      $count_query
        ->condition('c.entity_id', $entity->id())
        ->condition('c.entity_type', $entity->getEntityTypeId())
        ->condition('c.field_name', $field_name)
        ->condition('c.default_langcode', 1)
        ->addTag('entity_access')
        ->addTag('comment_filter')
        ->addMetaData('base_table', 'comment')
        ->addMetaData('entity', $entity)
        ->addMetaData('field_name', $field_name);
      $query->setCountQuery($count_query);
    }

    if (!$items->access('administer comments')) {
      $query->condition('c.status', CommentInterface::PUBLISHED);
      if ($comments_per_page) {
        $count_query->condition('c.status', CommentInterface::PUBLISHED);
      }
    }
    if ($mode == CommentManagerInterface::COMMENT_MODE_FLAT) {
      $query->orderBy('c.cid', 'ASC');
    }
    else {
      // See comment above. Analysis reveals that this doesn't cost too
      // much. It scales much much better than having the whole comment
      // structure.
      $query->addExpression('SUBSTRING(c.thread, 1, (LENGTH(c.thread) - 1))', 'torder');
      $query->orderBy('torder', 'ASC');
    }

    $cids = $query->execute()->fetchCol();

    $comments = [];
    if ($cids) {
      $comments = $this->storage->loadMultiple($cids);
    }

    return $comments;
  }

}
