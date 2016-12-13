<?php

namespace Drupal\og_comment\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\comment\CommentInterface;
use Drupal\comment\CommentManagerInterface;
use Drupal\comment\Plugin\Field\FieldFormatter\CommentDefaultFormatter;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\og\OgAccessInterface;
use Drupal\og\OgGroupAudienceHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Overrides the default comment formatter.
 */
class OgCommentDefaultFormatter extends CommentDefaultFormatter {

  /**
   * Active database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The OG access service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * Helper for dealing with group audience fields.
   *
   * @var \Drupal\og\OgGroupAudienceHelperInterface
   */
  protected $ogGroupAudienceHelper;

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
      $container->get('og.group_audience_helper'),
      $container->get('og.access')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, EntityManagerInterface $entity_manager, EntityFormBuilderInterface $entity_form_builder, RouteMatchInterface $route_match, Connection $database, OgGroupAudienceHelperInterface $og_group_audience_helper, OgAccessInterface $og_access) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $current_user, $entity_manager, $entity_form_builder, $route_match);
    $this->database = $database;
    $this->ogAccess = $og_access;
    $this->ogGroupAudienceHelper = $og_group_audience_helper;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $output = [];

    $field_name = $this->fieldDefinition->getName();
    $entity = $items->getEntity();

    $status = $items->status;

    // If not Og content, fall back to normal comment rendering.
    if (!$this->ogGroupAudienceHelper->hasGroupAudienceField($entity->getEntityTypeId(), $entity->bundle())) {
      return parent::viewElements($items, $langcode);
    }

    if ($status != CommentItemInterface::HIDDEN && empty($entity->in_preview) &&
      // Comments are added to the search results and search index by
      // comment_node_update_index() instead of by this formatter, so don't
      // return anything if the view mode is search_index or search_result.
      !in_array($this->viewMode, array('search_result', 'search_index'))) {
      $comment_settings = $this->getFieldSettings();

      // Only attempt to render comments if the entity has visible comments.
      // Unpublished comments are not included in
      // $entity->get($field_name)->comment_count, but unpublished comments
      // should display if the user is an administrator.
      $elements['#cache']['contexts'][] = 'user.permissions';
      // @todo Fix in ISAICP-2898.
      $elements['#cache']['max-age'] = 0;
      if ($this->hasPermission('access comments', $items) || $this->hasPermission('administer comments', $items)) {
        $output['comments'] = [];

        if ($entity->get($field_name)->comment_count || $this->hasPermission('administer comments', $items)) {
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
        if ($this->hasPermission('post comments', $items)) {
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

      $elements[] = $output + array(
        '#comment_type' => $this->getFieldSetting('comment_type'),
        '#comment_display_mode' => $this->getFieldSetting('default_mode'),
        'comments' => array(),
        'comment_form' => array(),
      );
    }

    return $elements;
  }

  /**
   * Check if user has either global or group permission.
   *
   * @param string $permission
   *   The permission string to check.
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values to be rendered.
   *
   * @return bool|\Drupal\Core\Access\AccessResult
   *   True if the user has access to the items, false otherwise.
   */
  protected function hasPermission($permission, FieldItemListInterface $items) {
    $access = $this->currentUser->hasPermission($permission);
    // User has side-wide permission.
    if ($access) {
      return TRUE;
    }
    $host_entity = $entity = $items->getEntity();

    // Get group.
    $group_id = $host_entity->{OgGroupAudienceHelperInterface::DEFAULT_FIELD}->first()->target_id;
    if (!$group_id) {
      return $access;
    }
    /** @var \Drupal\field\Entity\FieldConfig $field_config */
    $field_config = $host_entity->{OgGroupAudienceHelperInterface::DEFAULT_FIELD}->first()->getFieldDefinition();
    /** @var \Drupal\field\Entity\FieldStorageConfig $storage_definition */
    $storage_definition = $field_config->getFieldStorageDefinition();
    $entity_type = $storage_definition->getSetting('target_type');

    $entity_storage = \Drupal::entityTypeManager()->getStorage($entity_type);
    $group = $entity_storage->load($group_id);

    $access = $this->ogAccess->userAccess($group, $permission, $this->currentUser);
    return ($access instanceof AccessResultAllowed);

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

    if (!$this->hasPermission('administer comments', $items)) {
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

    $comments = array();
    if ($cids) {
      $comments = $this->storage->loadMultiple($cids);
    }

    return $comments;
  }

}
