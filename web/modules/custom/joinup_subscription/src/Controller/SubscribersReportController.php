<?php

declare(strict_types = 1);

namespace Drupal\joinup_subscription\Controller;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\joinup_community_content\CommunityContentHelper;
use Drupal\joinup_group\Entity\GroupInterface;
use Drupal\joinup_group\JoinupGroupHelper;
use Drupal\sparql_entity_storage\Driver\Database\sparql\ConnectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generates a report detailing the number of subscribers in a(ll) group(s).
 */
class SubscribersReportController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The SQL database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $sqlConnection;

  /**
   * The SPARQL database connection.
   *
   * @var \Drupal\sparql_entity_storage\Driver\Database\sparql\ConnectionInterface
   */
  protected $sparqlConnection;

  /**
   * Constructs a new SubscribersReportController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $sqlConnection
   *   The SQL connection.
   * @param \Drupal\sparql_entity_storage\Driver\Database\sparql\ConnectionInterface $sparqlConnection
   *   The SPARQL connection.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, Connection $sqlConnection, ConnectionInterface $sparqlConnection) {
    $this->entityTypeManager = $entityTypeManager;
    $this->sqlConnection = $sqlConnection;
    $this->sparqlConnection = $sparqlConnection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('sparql.endpoint')
    );
  }

  /**
   * Returns a render array containing a table with subscriber data.
   *
   * @return array
   *   The render array.
   */
  public function build(): array {
    return [
      'table' => [
        '#type' => 'table',
        '#header' => [
          $this->t('Group'),
          $this->t('Type'),
          $this->t('Subscribers'),
          $this->t('Solution'),
          $this->t('Discussion'),
          $this->t('Document'),
          $this->t('Event'),
          $this->t('News'),
        ],
        '#rows' => $this->getSubscriberData(),
      ],
    ];
  }

  /**
   * Returns a data table containing information about the group's subscribers.
   *
   * @param \Drupal\joinup_group\Entity\GroupInterface|null $group
   *   Optional group entity for which to return subscriber counts. If omitted,
   *   data will be returned for all groups.
   *
   * @return \Drupal\user\UserInterface[]
   *   The list of subscribers as an array of user accounts, keyed by user ID.
   */
  protected function getSubscriberData(?GroupInterface $group = NULL): array {
    // Optionally filter the results by group.
    $where_clause = $group ? 'WHERE m.entity_id = :group_id' : '';
    $query_args = $group ? ['group_id' => $group->id()] : [];

    // Retrieve the total number of subscribers.
    $query = <<<SQL
SELECT m.entity_id, COUNT(DISTINCT(b.entity_id)) as subscribers
FROM {og_membership} m
LEFT JOIN {og_membership__subscription_bundles} b ON m.id = b.entity_id
$where_clause
GROUP BY m.entity_id;
SQL;
    $subscribers = $this->sqlConnection->query($query, $query_args)->fetchAllAssoc('entity_id', \PDO::FETCH_ASSOC);

    // Prepare the data array with empty values to be filled in.
    $data = array_fill_keys(array_keys($subscribers), [
      'label' => $group ? $group->label() : '',
      'bundle' => $group ? $group->bundle() : '',
      'subscribers' => 0,
      'solution' => 0,
    ] + array_fill_keys(CommunityContentHelper::BUNDLES, 0));
    $data = NestedArray::mergeDeep($data, $subscribers);

    // Retrieve the number of subscribers by bundle.
    $query = <<<SQL
SELECT m.entity_id, COUNT(*) as count, b.subscription_bundles_bundle as bundle
FROM {og_membership} m
LEFT JOIN {og_membership__subscription_bundles} b ON m.id = b.entity_id
$where_clause
GROUP BY m.entity_id, b.subscription_bundles_bundle;
SQL;
    $subscribers_by_bundle = $this->sqlConnection->query($query, $query_args)->fetchAll(\PDO::FETCH_NUM);
    foreach ($subscribers_by_bundle as [$entity_id, $count, $bundle]) {
      $data[$entity_id][$bundle] = $count;
    }

    // Populate group labels. These need to be retrieved from SPARQL storage. If
    // we are only returning data for a single group this can be skipped since
    // we already know the label.
    if (!$group) {
      foreach ($this->getGroupInfo() as $entity_id => [$label, $bundle]) {
        if (array_key_exists($entity_id, $data)) {
          $data[$entity_id]['label'] = $label;
          $data[$entity_id]['bundle'] = $bundle;
        }
      }
    }

    // Strip out the entity ID, so it will not be shown in the table.
    array_walk($data, function (array &$row): void {
      unset($row['entity_id']);
    });

    return $data;
  }

  /**
   * Returns all group labels and bundles keyed by entity ID.
   *
   * @return array[]
   *   An array of group data, each item an array with two elements:
   *   - The group label.
   *   - The group bundle.
   */
  protected function getGroupInfo(): array {
    $info = [];

    foreach (JoinupGroupHelper::GROUP_BUNDLES as $bundle) {
      $query = <<<SPARQL
SELECT ?entity_id ?label
FROM <http://joinup.eu/$bundle/published>
WHERE {
  ?entity_id a <http://www.w3.org/ns/dcat#Catalog> .
  ?entity_id <http://purl.org/dc/terms/title> ?label .
}
ORDER BY ASC(?label)
SPARQL;

      foreach ($this->sparqlConnection->query($query) as $result) {
        $info[$result->entity_id->getUri()] = [
          $result->label->getValue(),
          $bundle,
        ];
      }
    }
    return $info;
  }

}
