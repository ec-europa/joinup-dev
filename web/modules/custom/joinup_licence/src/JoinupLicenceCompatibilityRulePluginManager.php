<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\joinup_licence\Annotation\JoinupLicenceCompatibilityRule;
use Drupal\joinup_licence\Entity\LicenceInterface;

/**
 * Plugin manager for JoinupLicenceCompatibilityRule plugins.
 */
class JoinupLicenceCompatibilityRulePluginManager extends DefaultPluginManager {

  /**
   * The ID of the compatibility document for incompatible licences.
   */
  const INCOMPATIBLE_DOCUMENT_ID = 'INCOMPATIBLE';

  /**
   * Constructs a JoinupLicenceCompatibilityRulePluginManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/JoinupLicenceCompatibilityRule',
      $namespaces,
      $module_handler,
      JoinupLicenceCompatibilityRuleInterface::class,
      JoinupLicenceCompatibilityRule::class
    );
    $this->alterInfo('joinup_licence_compatibility_rule_info');
    $this->setCacheBackend($cache_backend, 'joinup_licence_compatibility_rule_plugins');
  }

  /**
   * Returns a document ID that details how the licence can be redistributed.
   *
   * This document contains advice how code or data which is distributed under
   * the current licence can be used in a project which is going to be
   * distributed under the passed in licence.
   *
   * @param \Drupal\joinup_licence\Entity\LicenceInterface $use_licence
   *   The licence of an existing project of which the code or data is used.
   * @param \Drupal\joinup_licence\Entity\LicenceInterface $redistribute_as_licence
   *   The licence under which the modified or extended code or data is going to
   *   be redistributed.
   *
   * @return string
   *   The document ID of the compatibility document that contains the requested
   *   information. If the licences are not compatible, the 'INCOMPATIBLE' is
   *   returned.
   */
  public function getCompatibilityDocumentId(LicenceInterface $use_licence, LicenceInterface $redistribute_as_licence): string {
    // Sort the plugins by weight and return the first result.
    $plugin_definitions = $this->getDefinitions();
    uasort($plugin_definitions, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);

    foreach ($plugin_definitions as $plugin_definition) {
      /** @var \Drupal\joinup_licence\JoinupLicenceCompatibilityRuleInterface $plugin */
      $plugin = $this->createInstance($plugin_definition['id']);
      if ($plugin->isCompatible($use_licence, $redistribute_as_licence) || $plugin_definition['document_id'] === static::INCOMPATIBLE_DOCUMENT_ID) {
        return $plugin->getDocumentId();
      }
    }

    throw new \RuntimeException("There should be a plugin with document_id equals '" . static::INCOMPATIBLE_DOCUMENT_ID . "' but is missed.");
  }

}
