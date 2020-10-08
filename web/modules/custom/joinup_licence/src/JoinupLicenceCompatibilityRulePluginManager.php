<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence;

use Drupal\Component\Utility\SortArray;
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
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the plugin being created is missing.
   */
  public function getCompatibilityDocumentId(LicenceInterface $use_licence, LicenceInterface $redistribute_as_licence): string {
    foreach ($this->getDefinitions() as $plugin_definition) {
      /** @var \Drupal\joinup_licence\JoinupLicenceCompatibilityRuleInterface $plugin */
      $plugin = $this->createInstance($plugin_definition['id']);
      if ($plugin->isCompatible($use_licence, $redistribute_as_licence) || $plugin_definition['document_id'] === static::INCOMPATIBLE_DOCUMENT_ID) {
        // Return the first compatible result. Note that the plugin definitions
        // were already sorted by their weight after discovery.
        return $plugin->getDocumentId();
      }
    }

    throw new \RuntimeException("There should be a plugin with document_id equals '" . static::INCOMPATIBLE_DOCUMENT_ID . "' but is missed.");
  }

  /**
   * {@inheritdoc}
   */
  protected function findDefinitions(): array {
    $plugin_definitions = parent::findDefinitions();

    // Extract the incompatible licence plugin from the list. We'll append it,
    // later, to the end of the list.
    $incompatible_plugin_definition = NULL;
    foreach ($plugin_definitions as $plugin_definition) {
      if ($plugin_definition['document_id'] === static::INCOMPATIBLE_DOCUMENT_ID) {
        $incompatible_plugin_definition = $plugin_definition;
      }
    }
    if (!$incompatible_plugin_definition) {
      throw new \RuntimeException("There should be a plugin with document_id equals '" . static::INCOMPATIBLE_DOCUMENT_ID . "' but is missed.");
    }
    unset($plugin_definitions[$incompatible_plugin_definition['id']]);

    // Sort the plugins by weight.
    uasort($plugin_definitions, [SortArray::class, 'sortByWeightElement']);

    // The incompatible licence plugin should be always the last, regardless of
    // its weight.
    $plugin_definitions[$incompatible_plugin_definition['id']] = $incompatible_plugin_definition;

    return $plugin_definitions;
  }

}
