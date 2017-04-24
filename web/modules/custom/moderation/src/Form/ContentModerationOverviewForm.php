<?php

namespace Drupal\moderation\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Database\Connection;
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
   * Constructs a new ContentModerationOverviewForm object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
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
    $sql = <<<SQL
      SELECT n.type, s.field_state_value as state, COUNT(1) as count
      FROM node n
      LEFT JOIN node__field_state s ON n.nid = s.entity_id
      WHERE n.type in (:types[])
      AND s.field_state_value in (:states[])
      GROUP BY s.field_state_value, n.type;
SQL;

    $args = [
      ':types[]' => ['discussion', 'document', 'event', 'news'],
      ':states[]' => CommunityContentHelper::getModeratorAttentionNeededStates(),
    ];
    $query = $this->connection->query($sql, $args);
    $result = $query->fetchAll(\PDO::FETCH_ASSOC);

    // Turn the count query result into a hierarchical array, keyed by bundle.
    $count = array_reduce($result, function ($count, $row) {
      $count[$row['type']][$row['state']] = $row['count'];
      return $count;
    }, []);
    ksort($count);

    $form = [
      'filter' => [
        '#type' => 'container',
        '#attributes' => ['class' => 'filter'],
        'type' => [
          '#type' => 'select',
          '#title' => 'Content of type',
          '#options' => $this->getTypeFilterOptions($count),
        ],
        'state' => [
          '#type' => 'select',
          '#title' => 'in state',
          '#options' => $this->getStateFilterOptions($count),
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $todo = TRUE;
  }

  /**
   * Access check for the content moderation overview.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The collection that is on the verge of losing a member.
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

    // Only allow access if the current user is a moderator or a collection
    // facilitator.
    if (in_array('moderator', $user->getRoles())) {
      $access = TRUE;
    }
    elseif ($membership_manager->isMember($rdf_entity, $user)) {
      $membership = $membership_manager->getMembership($rdf_entity, $user);
      if (in_array('rdf_entity-collection-facilitator', $membership->getRolesIds())) {
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
   *
   * @return array
   *   An associative array of select options, keyed by moderation state.
   */
  protected function getStateFilterOptions(array $content_count) {
    $options = [];
    $total_count = 0;

    foreach ($content_count as $type => $states_count) {
      foreach ($states_count as $state => $state_count) {
        if (empty($options[$state])) {
          $options[$state] = 0;
        }
        $options[$state] += $state_count;
        $total_count += $state_count;
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

}
