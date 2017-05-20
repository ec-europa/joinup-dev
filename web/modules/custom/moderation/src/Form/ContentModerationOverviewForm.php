<?php

namespace Drupal\moderation\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\joinup_community_content\CommunityContentHelper;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form that allows to display and filter the content moderation overview.
 */
class ContentModerationOverviewForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The node storage class.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * Constructs a new ContentModerationOverviewForm object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(Connection $connection, EntityTypeManagerInterface $entityTypeManager) {
    $this->connection = $connection;
    $this->entityTypeManager = $entityTypeManager;
    $this->nodeStorage = $this->entityTypeManager->getStorage('node');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_moderation_overview_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, RdfInterface $rdf_entity = NULL) {
    $result = $this->getModerationItems($rdf_entity);
    $count = $this->getModerationItemCount($result);

    // Generate the filter form elements.
    $type_filter = $form_state->getValue('type');
    $state_filter = $form_state->getValue('state');
    $form = $this->buildSelectForm($count, $type_filter);

    // Retrieve the entities that need moderation. Only execute this query when
    // there actually are results to fetch.
    if ($this->getFilteredItemsCount($count, $type_filter, $state_filter)) {
      $entities = $this->loadModeratedEntities($rdf_entity, $type_filter, $state_filter);
      $form['wrapper']['content'][] = $this->entityTypeManager->getViewBuilder('node')
        ->viewMultiple($entities, 'moderation');
    }
    else {
      $form['wrapper']['content'] = $this->buildNoResultsForm();;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Ajax callback.
   *
   * This returns the updated form after changing the filter options.
   */
  public static function updateForm(array $form, FormStateInterface $form_state) {
    return $form['wrapper'];
  }

  /**
   * Access check for the content moderation overview.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The collection or solution that is being moderated.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result object.
   */
  public static function access(RdfInterface $rdf_entity) {
    /** @var \Drupal\Core\Session\AccountProxyInterface $account_proxy */
    $account_proxy = \Drupal::service('current_user');
    $user = $account_proxy->getAccount();

    /** @var \Drupal\og\MembershipManagerInterface $membership_manager */
    $membership_manager = \Drupal::service('og.membership_manager');

    $access = FALSE;

    // Only allow access if the current user is a moderator or a facilitator.
    if (in_array('moderator', $user->getRoles())) {
      $access = TRUE;
    }
    elseif ($membership_manager->isMember($rdf_entity, $user)) {
      $membership = $membership_manager->getMembership($rdf_entity, $user);
      $role = $rdf_entity->bundle() === 'collection' ? 'rdf_entity-collection-facilitator' : 'rdf_entity-solution-facilitator';
      if (in_array($role, $membership->getRolesIds())) {
        $access = TRUE;
      }
    }

    return AccessResult::allowedIf($access);
  }

  /**
   * Returns the select options for the content type filter.
   *
   * @param array $content_count
   *   An associative array keyed by content type, each value an associative
   *   array keyed by moderation state, with the number of items as value.
   *
   * @return array
   *   An associative array of select options, keyed by content type.
   */
  protected function getTypeFilterOptions(array $content_count) {
    $options = [];
    $total_count = 0;

    foreach ($content_count as $type => $states_count) {
      $type_count = array_reduce($states_count, function ($total, $state_count) {
        return $total + $state_count;
      }, 0);
      $total_count += $type_count;
      $options[$type] = node_type_get_names()[$type] . " ($type_count)";
    }

    return ['all' => t("All (@count)", ['@count' => $total_count])] + $options;
  }

  /**
   * Returns the select options for the moderation state filter.
   *
   * @param array $content_count
   *   An associative array keyed by content type, each value an associative
   *   array keyed by moderation state, with the number of items as value.
   * @param string $content_type
   *   Optional content type for which to return the select options. If this is
   *   omitted or 'all', the select options for all content types will be
   *   returned.
   *
   * @return array
   *   An associative array of select options, keyed by moderation state.
   */
  protected function getStateFilterOptions(array $content_count, $content_type = NULL) {
    $options = [];
    $total_count = 0;

    foreach ($content_count as $type => $states_count) {
      foreach ($states_count as $state => $state_count) {
        if (in_array($content_type, [NULL, 'all', $type])) {
          if (empty($options[$state])) {
            $options[$state] = 0;
          }
          $options[$state] += $state_count;
          $total_count += $state_count;
        }
      }
    }
    ksort($options);

    array_walk($options, function (&$value, $state) {
      // Humanize the state IDs by removing the underscores and capitalizing the
      // first letter.
      $value = Unicode::ucfirst(strtr($state, ['_' => ' '])) . " ($value)";
    });

    return ['all' => t("All (@count)", ['@count' => $total_count])] + $options;
  }

  /**
   * Returns the number of items are matching the given filters.
   *
   * @param array $content_count
   *   An associative array keyed by content type, each value an associative
   *   array keyed by moderation state, with the number of items as value.
   * @param string $type_filter
   *   Optional content type for which to return the count. If this is omitted
   *   or 'all', the count for all content types will be returned.
   * @param string $state_filter
   *   Optional workflow state for which to return the count. If this is omitted
   *   or 'all', the count for all workflow states will be returned.
   *
   * @return int
   *   The number of items that match the given filters.
   */
  protected function getFilteredItemsCount(array $content_count, $type_filter = NULL, $state_filter = NULL) {
    $count = 0;
    foreach ($content_count as $type => $states_count) {
      foreach ($states_count as $state => $state_count) {
        $valid_types = [NULL, 'all', $type];
        $valid_states = [NULL, 'all', $state];
        if (in_array($type_filter, $valid_types) && in_array($state_filter, $valid_states)) {
          $count += $state_count;
        }
      }
    }

    return $count;
  }

  /**
   * Builds the filers on content type and state.
   *
   * @param array $count
   *   An associative array keyed by content type, each value an associative
   *   array keyed by moderation state, with the number of items as value.
   * @param string $type_filter
   *   The active content type filter.
   *
   * @return array
   *   The form array with the content type and state filters.
   */
  protected function buildSelectForm(array $count, $type_filter) {
    $form = [
      'wrapper' => [
        '#type' => 'container',
        '#attributes' => ['id' => 'ajax-wrapper'],
        'filter' => [
          '#type' => 'container',
          '#attributes' => ['class' => 'filter'],
          'type' => [
            '#type' => 'select',
            '#title' => 'Content of type',
            '#options' => $this->getTypeFilterOptions($count),
            '#ajax' => [
              'callback' => '::updateForm',
              'wrapper' => 'ajax-wrapper',
              'effect' => 'fade',
            ],
          ],
          'state' => [
            '#type' => 'select',
            '#title' => 'in state',
            '#options' => $this->getStateFilterOptions($count, $type_filter),
            '#ajax' => [
              'callback' => '::updateForm',
              'wrapper' => 'ajax-wrapper',
              'effect' => 'fade',
            ],
          ],
        ],
      ],
    ];
    return $form;
  }

  /**
   * Build a  list of entities that need moderation.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The collection or solution that is being moderated.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The related entities.
   */
  protected function getModerationItems(RdfInterface $rdf_entity) {
    $entities = $this->nodeStorage->getQuery()
      ->condition('og_audience.target_id', $rdf_entity->id())
      ->condition('field_state', CommunityContentHelper::getModeratorAttentionNeededStates(), 'IN')
      ->condition('type', CommunityContentHelper::getBundles(), 'IN')
      ->allRevisions()
      ->execute();
    $return = [];

    // Filter out the non-latest versions.
    foreach ($entities as $vid => $nid) {
      if ($this->isLatestRevision($vid, $nid)) {
        $return[$nid] = $this->nodeStorage->loadRevision($vid);
      }
    }

    return $return;
  }

  /**
   * Returns the latest revision id of an entity.
   *
   * @param string $entity_id
   *   The entity id.
   *
   * @return mixed
   *   The revision id or null.
   */
  public function getLatestRevisionId($entity_id) {
    if ($storage = $this->entityTypeManager->getStorage('node')) {
      $revision_ids = $storage->getQuery()
        ->allRevisions()
        ->condition('nid', $entity_id)
        ->sort('vid', 'DESC')
        ->range(0, 1)
        ->execute();
      if ($revision_ids) {
        return array_keys($revision_ids)[0];
      }
    }
  }

  /**
   * Checks if the passed revision is the latest one.
   *
   * @param string $revision_id
   *   The revision id.
   * @param string $entity_id
   *   The entity id.
   *
   * @return bool
   *   Whether the revision is the latest.
   */
  public function isLatestRevision($revision_id, $entity_id) {
    return $revision_id == $this->getLatestRevisionId($entity_id);
  }

  /**
   * Build the item count by content type and state.
   *
   * Builds an associative array keyed by content type,
   * each value an associative array keyed by moderation state,
   * with the number of items as value.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $result
   *   An array of entities indexed by nid.
   *
   * @return array
   *   An associative array indexed by bundle and the the values being an
   *   associative array having the moderation state as a key and the count as
   *   a value.
   */
  protected function getModerationItemCount(array $result) {
    // Turn the count query result into a hierarchical array, keyed by bundle.
    $count = array_reduce($result, function ($count, EntityInterface $row) {
      $bundle = $row->bundle();
      $state = $row->get('field_state')->first()->value;
      if (!isset($count[$bundle][$state])) {
        $count[$bundle][$state] = 0;
      }
      $count[$bundle][$state]++;
      return $count;
    }, []);
    ksort($count);
    return $count;
  }

  /**
   * Loads the nodes that need moderation.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The collection or solution that is being moderated.
   * @param string $type_filter
   *   The active content type filter.
   * @param string $state_filter
   *   The active state filter.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]|static[]
   *   A list of loaded nodes.
   */
  protected function loadModeratedEntities(RdfInterface $rdf_entity, $type_filter, $state_filter) {
    $moderatable_types = CommunityContentHelper::getBundles();
    $moderatable_states = CommunityContentHelper::getModeratorAttentionNeededStates();
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $query->condition('og_audience', $rdf_entity->id());
    $query->allRevisions();
    if ($type_filter && $type_filter !== 'all') {
      $query->condition('type', $type_filter);
    }
    else {
      $query->condition('type', $moderatable_types, 'IN');
    }
    if ($state_filter && $state_filter !== 'all') {
      $query->condition('field_state', $state_filter);
    }
    else {
      $query->condition('field_state', $moderatable_states, 'IN');
    }

    // Build and return a list with the latest revisions.
    $return = [];
    foreach ($query->execute() as $nid) {
      $latest_revision_id = $this->getLatestRevisionId($nid);
      $return[$nid] = $this->nodeStorage->loadRevision($latest_revision_id);

    }
    return $return;
  }

  /**
   * Builds the content of the form when no entities need moderation.
   */
  protected function buildNoResultsForm() {
    return [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Nothing to moderate. Enjoy your day!'),
    ];
  }

}
