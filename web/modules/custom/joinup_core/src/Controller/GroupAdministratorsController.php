<?php

declare(strict_types = 1);

namespace Drupal\joinup_core\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\csv_serialization\Encoder\CsvEncoder;
use Drupal\joinup_core\JoinupRelationManagerInterface;
use Drupal\og\GroupTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Controller for routes that are showing reports of group administrators.
 */
class GroupAdministratorsController extends ControllerBase {

  /**
   * The OG role names corresponding to administrative roles.
   */
  const ADMINISTRATIVE_ROLES = ['administrator', 'facilitator'];

  /**
   * The OG group type manager.
   *
   * @var \Drupal\og\GroupTypeManager
   */
  protected $groupTypeManager;

  /**
   * The Joinup relation manager.
   *
   * @var \Drupal\joinup_core\JoinupRelationManagerInterface
   */
  protected $joinupRelationManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new OgAdminMembersController object.
   *
   * @param \Drupal\og\GroupTypeManager $groupTypeManager
   *   The OG group type manager.
   * @param \Drupal\joinup_core\JoinupRelationManagerInterface $joinupRelationManager
   *   The Joinup relation manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   The entity type bundle info service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match service.
   */
  public function __construct(GroupTypeManager $groupTypeManager, JoinupRelationManagerInterface $joinupRelationManager, EntityTypeBundleInfoInterface $entityTypeBundleInfo, RouteMatchInterface $routeMatch) {
    $this->groupTypeManager = $groupTypeManager;
    $this->joinupRelationManager = $joinupRelationManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->routeMatch = $routeMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('og.group_type_manager'),
      $container->get('joinup_core.relations_manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('current_route_match')
    );
  }

  /**
   * Returns the group administrators report.
   *
   * @param string $entity_type_id
   *   The entity type ID of the group type for which to generate the report.
   * @param string $bundle_id
   *   The bundle ID of the group type for which to generate the report.
   * @param string $format
   *   The data format to return, can be either 'html' or 'csv'. Defaults to
   *   'html'.
   *
   * @return \Symfony\Component\HttpFoundation\Response|array
   *   The page content, either as a Response object, or as a render array.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the given entity type and bundle do not correspond to a valid
   *   group type.
   */
  public function report(string $entity_type_id, string $bundle_id, string $format = 'html') {
    // Check if the passed in bundle is a group type. This is here for
    // completeness, the access check prevents this method from being invoked if
    // the bundle is not a group.
    if (!$this->groupTypeManager->isGroup($entity_type_id, $bundle_id)) {
      throw new \InvalidArgumentException('The group administrator report can only be shown for group types.');
    }

    $memberships = $this->getAdministrativeCollectionMemberships();

    return $format === 'csv' ? $this->getCsvResponse($memberships) : $this->getHtmlResponse($memberships);
  }

  /**
   * Returns the page title for the group administrators report.
   *
   * @param string $entity_type_id
   *   The entity type ID of the group type for which to generate the title.
   * @param string $bundle_id
   *   The bundle ID of the group type for which to generate the title.
   *
   * @return string
   *   The page title.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the label information for the given entity type and bundle is
   *   not available.
   */
  public function reportTitle(string $entity_type_id, string $bundle_id): string {
    $entity_bundle_info = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
    if (empty($entity_bundle_info[$bundle_id]['label'])) {
      throw new \InvalidArgumentException("The '$entity_type_id' entity type doesn't have the necessary information about the '$bundle_id'' bundle.");
    }
    return (string) $this->t('@bundle administrator report', ['@bundle' => Unicode::ucfirst($entity_bundle_info[$bundle_id]['label'])]);
  }

  /**
   * Checks access for the group administrators controller.
   *
   * @param string $entity_type_id
   *   The entity type ID of the group type for which to check access.
   * @param string $bundle_id
   *   The bundle ID of the group type for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(string $entity_type_id, string $bundle_id): AccessResultInterface {
    // Only allow access if the passed in bundle is a group.
    return AccessResult::allowedIf($this->groupTypeManager->isGroup($entity_type_id, $bundle_id));
  }

  /**
   * Returns a response containing a CSV table with the given memberships.
   *
   * @param \Drupal\og\OgMembershipInterface[] $memberships
   *   The memberships to include in the response.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response containing the table.
   */
  protected function getCsvResponse(array $memberships): Response {
    $headers = [
      (string) $this->t('Collection name'),
      (string) $this->t('Collection URL'),
      (string) $this->t('Username'),
      (string) $this->t('User profile URL'),
      (string) $this->t('User role'),
    ];

    $rows = [];
    foreach ($memberships as $membership) {
      foreach ($membership->getRoles() as $role) {
        if (in_array($role->getName(), self::ADMINISTRATIVE_ROLES)) {
          $group = $membership->getGroup();
          try {
            $group_url = $group->toUrl()->setAbsolute()->toString();
          }
          catch (EntityMalformedException $e) {
            // If the group URL cannot be generated, leave it out of the data.
            $group_url = '';
          }

          $user = $membership->getOwner();
          $username = joinup_user_get_display_name($user);
          try {
            $user_url = $user->toUrl()->setAbsolute()->toString();
          }
          catch (EntityMalformedException $e) {
            // If the user URL cannot be generated, leave it out of the data.
            $user_url = '';
          }

          $rows[] = array_combine($headers, [
            $group->label(),
            $group_url,
            $username,
            $user_url,
            $role->getName(),
          ]);
        }
      }
    }

    $data = (new CsvEncoder())->encode($rows, 'csv');

    // Create a response that downloads the data instead of displaying it in the
    // browser.
    $response = new Response($data);
    $content_disposition_header = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'collection-administrators.csv');
    $response->headers->set('Content-Disposition', $content_disposition_header);
    $response->headers->set('Content-Type', 'text/csv');

    return $response;
  }

  /**
   * Returns a render array containing a HTML table with the given memberships.
   *
   * @param \Drupal\og\OgMembershipInterface[] $memberships
   *   The memberships to display.
   *
   * @return array
   *   The render array containing the table.
   */
  protected function getHtmlResponse(array $memberships): array {
    $rows = [];
    foreach ($memberships as $membership) {
      foreach ($membership->getRoles() as $role) {
        if (in_array($role->getName(), self::ADMINISTRATIVE_ROLES)) {
          $group = $membership->getGroup();
          $user = $membership->getOwner();
          $username = joinup_user_get_display_name($user);
          try {
            $group_cell = [
              '#type' => 'link',
              '#title' => $group->label(),
              '#url' => $group->toUrl(),
            ];
          }
          catch (EntityMalformedException $e) {
            // A canonical URL to the group cannot be generated, show only the
            // group title.
            $group_cell = ['#markup' => $group->label()];
          }

          try {
            $user_cell = [
              '#type' => 'link',
              '#title' => $username,
              '#url' => $user->toUrl(),
            ];
          }
          catch (EntityMalformedException $e) {
            // A canonical URL to the user profile cannot be generated, show
            // only the user name.
            $user_cell = ['#markup' => $username];
          }
          $rows[] = [$group_cell, $user_cell, ['#markup' => $role->getName()]];
        }
      }
    }

    // Add a link to download the report as a CSV file.
    $url = Url::fromRouteMatch($this->routeMatch);
    $url->setRouteParameter('format', 'csv');

    // Return the table as a render array, including the link and table headers.
    return [
      'link' => [
        '#type' => 'link',
        '#title' => $this->t('Download CSV'),
        '#url' => $url,
        '#attributes' => ['class' => ['button']],
      ],
      'table' => $rows + [
        '#type' => 'table',
        '#header' => [
          $this->t('Collection'),
          $this->t('User name'),
          $this->t('Role'),
        ],
        '#attributes' => ['class' => ['collection-administrator-report']],
      ],
    ];
  }

  /**
   * Returns the entity storage for the given entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID for which to return the entity storage. Should be
   *   either 'og_membership' or 'rdf_entity'.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The entity storage.
   */
  protected function getEntityStorage(string $entity_type_id): EntityStorageInterface {
    try {
      $storage = $this->entityTypeManager()->getStorage($entity_type_id);
    }
    catch (InvalidPluginDefinitionException $e) {
      // The Joinup Core module depends on the OG and RDF Entity modules which
      // have sufficient test coverage so we can reasonably expect that their
      // entity storage definitions are valid. If not this is due to exceptional
      // circumstances occurring at runtime.
      throw new \RuntimeException("The definition of the $entity_type_id entity storage plugin is not valid.");
    }
    catch (PluginNotFoundException $e) {
      // Since the Joinup Core module depends on the OG and RDF Entity modules
      // we can reasonably expect that their entity storage definitions are
      // available. If not this is due to exceptional circumstances occurring at
      // runtime.
      throw new \RuntimeException("The $entity_type_id entity storage is not defined.");
    }

    return $storage;
  }

  /**
   * Returns the OG Memberships of all collection owners and facilitators.
   *
   * @return \Drupal\og\OgMembershipInterface[]
   *   The memberships.
   */
  protected function getAdministrativeCollectionMemberships(): array {
    // Load the full list of memberships and filter out the non-administrators.
    // Since this is a moderator-only feature that is rarely used we don't need
    // to worry about the potential performance impact of loading a large number
    // of memberships.
    $collection_ids = $this->joinupRelationManager->getCollectionIds();

    if (empty($collection_ids)) {
      return [];
    }

    $roles = array_map(function (string $role_name): string {
      return implode('-', ['rdf_entity', 'collection', $role_name]);
    }, self::ADMINISTRATIVE_ROLES);

    $membership_storage = $this->getEntityStorage('og_membership');
    $query = $membership_storage->getQuery();
    $membership_ids = $query
      ->condition('entity_type', 'rdf_entity')
      ->condition('entity_id', $collection_ids, 'IN')
      ->condition('roles', $roles, 'IN')
      ->execute();

    return $membership_storage->loadMultiple($membership_ids);
  }

}
