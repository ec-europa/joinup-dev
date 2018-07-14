<?php

declare(strict_types = 1);

namespace Drupal\tallinn\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\og\OgAccessInterface;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\tallinn\Plugin\Field\FieldType\TallinnEntryItem;
use Drupal\tallinn\Tallinn;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a controller for the 'tallinn.dashboard' route.
 */
class DashboardController extends ControllerBase {

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The organic groups access service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * Static cache of country list.
   *
   * @var string[]
   */
  protected static $countries;

  /**
   * Constructs a new controller.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The organic groups access service.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager, ModuleHandlerInterface $module_handler, OgAccessInterface $og_access) {
    $this->entityFieldManager = $entity_field_manager;
    $this->moduleHandler = $module_handler;
    $this->ogAccess = $og_access;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_field.manager'),
      $container->get('module_handler'),
      $container->get('og.access')
    );
  }

  /**
   * Returns the backend data formatted as Json.
   *
   * We return a cached serialized Json blob. The response cache have
   * dependencies on the Tallinn collection and on each report. Any changes to
   * these entities will invalidate the response cache.
   *
   * @return \Drupal\Core\Cache\CacheableResponseInterface
   *   The cached Json response.
   */
  public function getData(Request $request): CacheableResponseInterface {
    $field_definitions = $this->entityFieldManager->getFieldDefinitions('node', 'tallinn_report');
    $entity_form_display = EntityFormDisplay::load("node.tallinn_report.default");
    $groups = $entity_form_display->getThirdPartySettings('field_group');
    $reports = $this->getReports();
    $status_options = TallinnEntryItem::getStatusOptions();

    // Prepare the response early, so that we can add report entities and their
    // group collection as cacheable dependencies while building the response
    // content.
    $response = new CacheableResponse('', 200, [
      'Content-Type' => $request->getMimeType('json'),
    ]);
    // The response cache should be invalidated on any collection change.
    $response->addCacheableDependency(Rdf::load(TALLINN_COMMUNITY_ID));

    $data = [];
    foreach ($groups as $group_id => $group_info) {
      if (empty($group_info['parent_name'])) {
        // The fields are placed in a nested group. Ignore the wrapping group.
        continue;
      }

      $group = [
        'principle' => $group_info['label'],
        // @todo Clarify this value.
        'description' => "WE DO NOT HAVE THIS INFO!",
        'actions' => [],
      ];
      foreach ($group_info['children'] as $field_name) {
        $group['actions'][$field_name] = [
          'title' => $field_definitions[$field_name]->getLabel(),
          'explanation' => $field_definitions[$field_name]->getDescription(),
          'countries' => [],
        ];
      }

      foreach ($reports as $country_code => $report) {
        /** @var \Drupal\Core\Field\FieldItemInterface $field */
        foreach ($report as $field_name => $field) {
          if (isset($group['actions'][$field_name])) {
            $value = $field->first()->getValue();
            $group['actions'][$field_name]['countries'][$country_code] = [
              'country_name' => $report->label(),
              'status' => $status_options[$value['status']],
              'report' => check_markup($value['value'], $value['format']) ?: NULL,
              'related_website' => $value['uri'],
            ];
            // The Json response cache depends on all the reports.
            $response->addCacheableDependency($report);
          }
        }
      }

      // Remove the field name keys.
      $group['actions'] = array_values($group['actions']);
      $data[] = $group;
    }

    // Wrap data under 'JSON' key.
    $data = ['JSON' => $data];

    return $response->setContent(Json::encode($data));
  }

  /**
   * Checks the route access.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account of the user that is requesting the route.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result object.
   */
  public function access(AccountInterface $account): AccessResultInterface {
    $access_type = $this->config('tallinn.settings')->get('dashboard.access_type');

    return AccessResult::allowedIf(
      // Either the access is public.
      ($access_type === 'public') ||
      // Or the user has site-wide access permission.
      $account->hasPermission('administer tallinn settings') ||
      // Or the user has group access permission.
      $this->ogAccess->userAccess(Rdf::load(TALLINN_COMMUNITY_ID), 'administer tallinn settings')->isAllowed()
    );
  }

  /**
   * Returns a list of Tallinn report nodes keyed by the country code.
   *
   * @return \Drupal\node\NodeInterface[]
   *   A list of Tallinn report nodes keyed by the country code.
   */
  protected function getReports(): array {
    $nids = $this->entityTypeManager()->getStorage('node')->getQuery()
      ->condition('type', 'tallinn_report')
      ->execute();

    $reports = [];
    /** @var \Drupal\node\NodeInterface $report */
    foreach (Node::loadMultiple($nids) as $report) {
      $country_code = static::getCountryCode($report->label());
      $reports[$country_code] = $report;
    }
    ksort($reports);

    return $reports;
  }

  /**
   * Returns a country code given the country name.
   *
   * @param string $country_name
   *   The full name of the country.
   *
   * @return string
   *   The 2 letter country code.
   *
   * @throws \InvalidArgumentException
   *   If an non-existing country name has been passed.
   */
  protected static function getCountryCode(string $country_name): string {
    if (!isset(static::$countries)) {
      static::$countries = array_flip(Tallinn::COUNTRIES);
    }
    if (!isset(static::$countries[$country_name])) {
      throw new \InvalidArgumentException("Country $country_name not in the standard country list.");
    }
    return static::$countries[$country_name];
  }

}
