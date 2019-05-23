<?php

declare(strict_types = 1);

namespace Drupal\tallinn\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\tallinn\DashboardAccessInterface;
use Drupal\tallinn\Plugin\Field\FieldType\TallinnEntryItem;
use Drupal\tallinn\Tallinn;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * The dashboard access service.
   *
   * @var \Drupal\tallinn\DashboardAccessInterface
   */
  protected $dashboardAccess;

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
   * @param \Drupal\tallinn\DashboardAccessInterface $dashboard_access
   *   The dashboard access service.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager, DashboardAccessInterface $dashboard_access) {
    $this->entityFieldManager = $entity_field_manager;
    $this->dashboardAccess = $dashboard_access;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('entity_field.manager'),
      $container->get('tallinn.dashboard.access')
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
   *
   * @throws \LogicException
   *   If the group entity is missing.
   */
  public function getData(): CacheableResponseInterface {
    if (!$tallinn_collection = Rdf::load(Tallinn::COMMUNITY_ID)) {
      throw new \LogicException("The Tallinn collection entity is missing.");
    }

    $field_definitions = $this->entityFieldManager->getFieldDefinitions('node', 'tallinn_report');
    $entity_form_display = EntityFormDisplay::load("node.tallinn_report.default");
    $groups = $entity_form_display->getThirdPartySettings('field_group');
    $reports = $this->getReports();
    $status_options = TallinnEntryItem::getStatusOptions();

    // Prepare the response early, so that we can add report entities and their
    // group collection as cacheable dependencies while building the response
    // content.
    $response = new CacheableJsonResponse();

    // Tag the response cache with 'tallinn_dashboard' so that we can invalidate
    // it in \Drupal\tallinn\Form\TallinnSettingsForm::submitForm().
    // @see \Drupal\tallinn\Form\TallinnSettingsForm::submitForm()
    $cacheable_metadata = (new CacheableMetadata())->addCacheTags(['tallinn_dashboard']);
    $response->addCacheableDependency($cacheable_metadata);
    // The response cache should be invalidated on any collection change.
    $response->addCacheableDependency($tallinn_collection);

    $data = [];
    foreach ($groups as $group_info) {
      if (empty($group_info['parent_name'])) {
        // The fields are placed in a nested group. Ignore the wrapping group.
        continue;
      }

      $group = [
        'principle' => $group_info['label'],
        'description' => $group_info['format_settings']['description'] ?: NULL,
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
          if (isset($group['actions'][$field_name]) && !$field->isEmpty()) {
            $value = $field->first()->getValue();
            $group['actions'][$field_name]['countries'][$country_code] = [
              'country_name' => $report->label(),
              'status' => $status_options[$value['status']],
              'report' => check_markup($value['value'], $value['format']) ?: '',
              'related_website' => $value['uri'] ?? NULL,
            ];
          }
          // The Json response cache depends on each report.
          $response->addCacheableDependency($report);
        }
      }
      // Remove the field name keys.
      $group['actions'] = array_values($group['actions']);

      $data[] = $group;
    }

    return $response->setData($data);
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
    return $this->dashboardAccess->access($account);
  }

  /**
   * Returns a list of Tallinn report nodes keyed by the country code.
   *
   * @return \Drupal\node\NodeInterface[]
   *   A list of Tallinn report nodes keyed by the country code.
   */
  protected function getReports(): array {
    try {
      $report_nodes = $this->entityTypeManager()->getStorage('node')
        ->loadByProperties(['type' => 'tallinn_report']);
    }
    catch (InvalidPluginDefinitionException $e) {
      // The entity type manager might throw this if the Node entity definition
      // is invalid. We implicitly trust that the entity types provided by core
      // are valid, so we can turn this into an unchecked exception.
      throw new \RuntimeException('The Node entity definition is invalid.', 0, $e);
    }
    catch (PluginNotFoundException $e) {
      // We have a dependency on the Node module listed in our info file so we
      // can reasonably assume that this entity type will be available at
      // runtime.
      throw new \RuntimeException('The Node entity type is not defined.', 0, $e);
    }

    $reports = [];
    /** @var \Drupal\node\NodeInterface $report */
    foreach ($report_nodes as $report) {
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
