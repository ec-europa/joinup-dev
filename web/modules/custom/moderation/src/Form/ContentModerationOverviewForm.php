<?php

declare(strict_types = 1);

namespace Drupal\moderation\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\joinup_community_content\CommunityContentHelper;
use Drupal\joinup_group\Entity\GroupInterface;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form that allows to display and filter the content moderation overview.
 */
class ContentModerationOverviewForm extends FormBase {

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->nodeStorage = $this->entityTypeManager->getStorage('node');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'content_moderation_overview_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?RdfInterface $rdf_entity = NULL): array {
    $result = $this->getModerationItems($rdf_entity);
    $count = $this->getModerationItemCount($result);

    // Generate the filter form elements.
    $type_filter = $form_state->getValue('type');
    $state_filter = $form_state->getValue('state');
    $form = $this->buildSelectForm($count, $type_filter);

    // Retrieve the entities that need moderation. Only execute this query when
    // there actually are results to fetch.
    if ($result = $this->filterCountedItems($result, $type_filter, $state_filter)) {
      $form['wrapper']['content'][] = $this->entityTypeManager->getViewBuilder('node')
        ->viewMultiple($result, 'moderation');
    }
    else {
      $form['wrapper']['content'] = $this->buildNoResultsForm();
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
  }

  /**
   * Ajax callback.
   *
   * This returns the updated form after changing the filter options.
   */
  public static function updateForm(array $form, FormStateInterface $form_state): array {
    return $form['wrapper'];
  }

  /**
   * Access check for the content moderation overview.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The collection or solution that is being moderated.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result object.
   */
  public function access(RdfInterface $rdf_entity): AccessResultInterface {
    // The content moderation overview is accessible by moderators and]
    // facilitators, so access varies by user role and OG role.
    $cache_metadata = (new CacheableMetadata())->addCacheContexts(
      ['og_role', 'user.permissions']
    );

    // Check if the user has global permission to access all content moderation
    // overviews (this is granted to moderators).
    $user = $this->currentUser();
    $access = $user->hasPermission('access content moderation overview');

    // If the user doesn't have global permission, check if they have permission
    // inside the group.
    if (!$access && $rdf_entity instanceof GroupInterface) {
      $access = $rdf_entity->hasGroupPermission((int) $user->id(), 'access content moderation overview');
    }

    return AccessResult::allowedIf($access)->addCacheableDependency($cache_metadata);
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
  protected function getTypeFilterOptions(array $content_count): array {
    $options = [];
    $total_count = 0;

    foreach ($content_count as $type => $states_count) {
      $type_count = array_reduce($states_count, function ($total, $state_count) {
        return $total + $state_count;
      }, 0);
      $total_count += $type_count;
      $options[$type] = node_type_get_names()[$type] . " ($type_count)";
    }

    return [
      'all' => $this->t("All (@count)", ['@count' => $total_count]),
    ] + $options;
  }

  /**
   * Returns the select options for the moderation state filter.
   *
   * @param array $content_count
   *   An associative array keyed by content type, each value an associative
   *   array keyed by moderation state, with the number of items as value.
   * @param string|null $content_type
   *   Optional content type for which to return the select options. If this is
   *   omitted or 'all', the select options for all content types will be
   *   returned.
   *
   * @return array
   *   An associative array of select options, keyed by moderation state.
   */
  protected function getStateFilterOptions(array $content_count, ?string $content_type = NULL): array {
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

    return [
      'all' => $this->t("All (@count)", ['@count' => $total_count]),
    ] + $options;
  }

  /**
   * Returns the number of items are matching the given filters.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   An array of entities.
   * @param string|null $type_filter
   *   Optional content type for which to return the count. If this is omitted
   *   or 'all', the count for all content types will be returned.
   * @param string|null $state_filter
   *   Optional workflow state for which to return the count. If this is omitted
   *   or 'all', the count for all workflow states will be returned.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The filtered array.
   */
  protected function filterCountedItems(array $entities, ?string $type_filter = NULL, ?string $state_filter = NULL): array {
    if (!empty($type_filter) && $type_filter !== 'all') {
      $entities = array_filter($entities, function (EntityInterface $entity) use ($type_filter) {
        return $entity->bundle() === $type_filter;
      });
    }
    if (!empty($state_filter) && $state_filter !== 'all') {
      $entities = array_filter($entities, function (EntityInterface $entity) use ($state_filter) {
        return $entity->get('field_state')->first()->value === $state_filter;
      });
    }

    return $entities;
  }

  /**
   * Builds the filters on content type and state.
   *
   * @param array $count
   *   An associative array keyed by content type, each value an associative
   *   array keyed by moderation state, with the number of items as value.
   * @param string|null $type_filter
   *   The active content type filter. If omitted select options for all content
   *   types will be included.
   *
   * @return array
   *   The form array with the content type and state filters.
   */
  protected function buildSelectForm(array $count, ?string $type_filter): array {
    return [
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
  protected function getModerationItems(RdfInterface $rdf_entity): array {
    $entities = $this->nodeStorage->getQuery()
      ->condition('og_audience.target_id', $rdf_entity->id())
      ->condition('field_state', CommunityContentHelper::getModeratorAttentionNeededStates(), 'IN')
      ->condition('type', CommunityContentHelper::BUNDLES, 'IN')
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
   * Returns the latest revision ID of a node.
   *
   * @param string $entity_id
   *   The entity ID.
   *
   * @return int|null
   *   The revision ID or null if the entity is not found in the database.
   */
  public function getLatestRevisionId(string $entity_id): ?int {
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
   * @param int $revision_id
   *   The revision id.
   * @param string $entity_id
   *   The entity id.
   *
   * @return bool
   *   Whether the revision is the latest.
   */
  public function isLatestRevision(int $revision_id, string $entity_id): bool {
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
  protected function getModerationItemCount(array $result): array {
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
   * Builds the content of the form when no entities need moderation.
   */
  protected function buildNoResultsForm(): array {
    return [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Nothing to moderate. Enjoy your day!'),
    ];
  }

}
