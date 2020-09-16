<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\ParamConverter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\joinup_licence\Entity\LicenceInterface;
use Drupal\joinup_licence\LicenceComparerHelper;
use Symfony\Component\Routing\Route;

/**
 * Converts a list of SPDX IDs into a list of Joinup licences.
 */
class SpdxIdsParamConverter implements ParamConverterInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new param converter service instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route): bool {
    return isset($definition['type']) && $definition['type'] === 'spdx_ids_list';
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    // The {licences} route parameter is a concatenated list of SPDX IDs
    // separated by the semicolon character.
    $spdx_ids = array_filter(explode(';', $value));

    // Filter out duplicates.
    $spdx_ids = array_values(array_unique($spdx_ids));

    // At least two, but no more than LicenceComparerHelper::MAX_LICENCE_COUNT
    // SPDX IDs to be passed along the request, in order to have a comparision.
    if (count($spdx_ids) < 2 || count($spdx_ids) > LicenceComparerHelper::MAX_LICENCE_COUNT) {
      return NULL;
    }

    array_walk($spdx_ids, function (string &$spdx_id): void {
      // If the plus character "+" has been passed in the route parameter, it
      // was already converted into space. Revert it.
      $spdx_id = str_replace(' ', '+', $spdx_id);
    });

    $storage = $this->entityTypeManager->getStorage('rdf_entity');

    $actual_spdx_uris = $storage->getQuery()
      ->condition('rid', 'spdx_licence')
      ->condition('field_spdx_licence_id', $spdx_ids, 'IN')
      ->execute();

    // Some of the passed SPDX IDs were not retrieved from the database.
    if (count($spdx_ids) > count($actual_spdx_uris)) {
      return NULL;
    }

    $actual_licence_ids = $storage->getQuery()
      ->condition('rid', 'licence')
      ->condition('field_licence_spdx_licence', $actual_spdx_uris, 'IN')
      ->execute();

    // Some of the passed SPDX IDs don't have a related Joinup licence.
    if (count($spdx_ids) > count($actual_licence_ids)) {
      return NULL;
    }

    // Restore the original order as it has been passed in the route parameter.
    $licences = $storage->loadMultiple($actual_licence_ids);
    $spdx_ids_order = array_flip($spdx_ids);
    uasort($licences, function (LicenceInterface $licence_a, LicenceInterface $licence_b) use ($spdx_ids_order): int {
      $licence_a_spdx_id = $licence_a->getSpdxLicenceId();
      $licence_b_spdx_id = $licence_b->getSpdxLicenceId();
      return $spdx_ids_order[$licence_a_spdx_id] <=> $spdx_ids_order[$licence_b_spdx_id];
    });

    // An ordered list of Joinup licence entities keyed by their SPDX ID.
    return array_combine($spdx_ids, $licences);
  }

}
