<?php

namespace Drupal\moderation\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\joinup_community_content\CommunityContentHelper;
use Drupal\node\Entity\Node;
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
    $moderatable_types = CommunityContentHelper::getBundles();
    $moderatable_states = CommunityContentHelper::getModeratorAttentionNeededStates();

    // Retrieve the number of content items that need moderation.
    $sql = <<<SQL
      SELECT n.type, s.field_state_value as state, COUNT(1) as count
      FROM node n
      LEFT JOIN node__field_state s ON n.nid = s.entity_id
      WHERE n.type in (:types[])
      AND s.field_state_value in (:states[])
      GROUP BY s.field_state_value, n.type;
SQL;

    $args = [
      ':types[]' => $moderatable_types,
      ':states[]' => $moderatable_states,
    ];

    $query = $this->connection->query($sql, $args);
    $result = $query->fetchAll(\PDO::FETCH_ASSOC);

    // Turn the count query result into a hierarchical array, keyed by bundle.
    $count = array_reduce($result, function ($count, $row) {
      $count[$row['type']][$row['state']] = $row['count'];
      return $count;
    }, []);
    ksort($count);

    // Generate the filter form elements.
    $type_filter = $form_state->getValue('type');
    $state_filter = $form_state->getValue('state');
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

    // Retrieve the entities that need moderation. Only execute this query when
    // there actually are results to fetch.
    if ($this->getFilteredItemsCount($count, $type_filter, $state_filter)) {
      $query = $this->entityTypeManager->getStorage('node')->getQuery();
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
      $entities = Node::loadMultiple($query->execute());
      $form['wrapper']['content'][] = $this->entityTypeManager->getViewBuilder('node')->viewMultiple($entities, 'moderation');
    }
    else {
      $form['wrapper']['content'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Nothing to moderate. Enjoy your day!'),
      ];
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

}
