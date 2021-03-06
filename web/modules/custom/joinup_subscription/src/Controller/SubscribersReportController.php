<?php

declare(strict_types = 1);

namespace Drupal\joinup_subscription\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use Drupal\Core\Utility\TableSort;
use Drupal\csv_serialization\Encoder\CsvEncoder;
use Drupal\joinup_community_content\CommunityContentHelper;
use Drupal\joinup_group\Entity\GroupInterface;
use Drupal\joinup_group\JoinupGroupHelper;
use Drupal\sparql_entity_storage\Driver\Database\sparql\ConnectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Generates a report detailing the number of subscribers in a(ll) group(s).
 */
class SubscribersReportController extends ControllerBase {

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
   * The Symfony request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new SubscribersReportController.
   *
   * @param \Drupal\Core\Database\Connection $sqlConnection
   *   The SQL connection.
   * @param \Drupal\sparql_entity_storage\Driver\Database\sparql\ConnectionInterface $sparqlConnection
   *   The SPARQL connection.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(Connection $sqlConnection, ConnectionInterface $sparqlConnection, RequestStack $requestStack) {
    $this->sqlConnection = $sqlConnection;
    $this->sparqlConnection = $sparqlConnection;
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('database'),
      $container->get('sparql.endpoint'),
      $container->get('request_stack')
    );
  }

  /**
   * Returns a table with subscriber data across all groups.
   *
   * @return array
   *   The table as a render array.
   */
  public function fullReport(): array {
    $headers = [
      [
        'data' => $this->t('Group'),
        'field' => 'label',
        'sort' => 'asc',
      ],
      [
        'data' => $this->t('Type'),
        'field' => 'bundle',
      ],
      [
        'data' => $this->t('Subscribers'),
        'field' => 'subscribers',
        'initial_click_sort' => 'desc',
      ],
      [
        'data' => $this->t('Solution'),
        'field' => 'solution',
        'initial_click_sort' => 'desc',
      ],
      [
        'data' => $this->t('Discussion'),
        'field' => 'discussion',
        'initial_click_sort' => 'desc',
      ],
      [
        'data' => $this->t('Document'),
        'field' => 'document',
        'initial_click_sort' => 'desc',
      ],
      [
        'data' => $this->t('Event'),
        'field' => 'event',
        'initial_click_sort' => 'desc',
      ],
      [
        'data' => $this->t('News'),
        'field' => 'news',
        'initial_click_sort' => 'desc',
      ],
    ];

    $data = $this->getSubscriberData();

    // Sort the table according to the options passed in the query arguments.
    // @see \Drupal\Core\Utility\TableSort
    $request = $this->requestStack->getCurrentRequest();
    $sort = TableSort::getSort($headers, $request);
    $order_by = TableSort::getOrder($headers, $request)['sql'];
    $this->sortData($data, $sort, $order_by);

    // Turn the group labels into links.
    array_walk($data, function (array &$row): void {
      $route_params = ['rdf_entity' => $row['entity_id']];
      $row['label'] = new FormattableMarkup('<a href=":uri">@label</a>', [
        ':uri' => (new Url('entity.rdf_entity.canonical', $route_params))->toString(),
        '@label' => $row['label'],
      ]);
      unset($row['entity_id']);
    });

    return [
      'table' => [
        '#type' => 'table',
        '#header' => $headers,
        '#rows' => $data,
        '#attributes' => ['class' => ['global-subscribers-report']],
      ],
      'download' => [
        '#theme' => 'download_link',
        '#url' => Url::fromRoute('joinup_subscription.subscribers_report_download'),
        '#attributes' => ['class' => ['button', 'button--primary']],
        '#title' => $this->t('Download CSV'),
        '#access' => $this->currentUser()->hasPermission('download subscribers report'),
      ],
    ];
  }

  /**
   * Returns a subscribers report for a single group.
   *
   * @param \Drupal\joinup_group\Entity\GroupInterface $rdf_entity
   *   The group for which to return the report.
   *
   * @return array[]
   *   A render array containing subscriber data.
   */
  public function groupReport(GroupInterface $rdf_entity): array {
    $data = $this->getSubscriberData($rdf_entity);
    $group_data = $data[$rdf_entity->id()] ?? NULL;

    if (!empty($group_data)) {
      $rows = [
        [$this->t('Subscribers'), $group_data['subscribers']],
        [$this->t('Solution'), $group_data['solution']],
        [$this->t('Discussion'), $group_data['discussion']],
        [$this->t('Document'), $group_data['document']],
        [$this->t('Event'), $group_data['event']],
        [$this->t('News'), $group_data['news']],
      ];
      return [
        'table' => [
          '#theme' => 'vertical_table',
          '#rows' => $rows,
          '#attributes' => ['class' => ['group-subscribers-report']],
        ],
      ];
    }

    return [
      'message' => [
        '#markup' => $this->t('No subscribers data is available'),
      ],
    ];
  }

  /**
   * Serves the subscriber report as a CSV file download.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The CSV file as a download.
   */
  public function download(): Response {
    $data = $this->getSubscriberData();

    // Strip out the entity ID, this is not desired in the exported data.
    array_walk($data, function (array &$row): void {
      unset($row['entity_id']);
    });

    $this->sortData($data);

    $csv = (new CsvEncoder())->encode($data, 'csv');
    $filename = 'subscribers-report-' . date('Y-m-d') . '.csv';

    $response = new Response($csv);
    $response->headers->set('Content-Disposition', "attachment; filename=\"$filename\"");
    $response->headers->set('Content-Type', 'text/csv');

    return $response;
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
INNER JOIN {og_membership__subscription_bundles} b ON m.id = b.entity_id
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
INNER JOIN {og_membership__subscription_bundles} b ON m.id = b.entity_id
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
      $rdf_type = $bundle === 'collection' ? 'http://www.w3.org/ns/dcat#Catalog' : 'http://www.w3.org/ns/dcat#Dataset';
      $query = <<<SPARQL
SELECT ?entity_id ?label
FROM <http://joinup.eu/$bundle/published>
WHERE {
  ?entity_id a <$rdf_type> .
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

  /**
   * Sorts the given data using the query arguments on the current request.
   *
   * @param array $data
   *   The data to sort.
   * @param string|null $sort
   *   The sorting order, either 'asc' or 'desc'. Defaults to 'asc'.
   * @param string|null $order_by
   *   The column to sort. Defaults to 'label'.
   */
  protected function sortData(array &$data, ?string $sort = 'asc', ?string $order_by = 'label'): void {
    usort($data, function (array $a, array $b) use ($sort, $order_by): int {
      $result = $a[$order_by] <=> $b[$order_by];
      return $sort === 'asc' ? $result : -$result;
    });
  }

}
