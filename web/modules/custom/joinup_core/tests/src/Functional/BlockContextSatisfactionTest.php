<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_core\Functional;

use Drupal\Core\Extension\Extension;

/**
 * Tests that our custom blocks satisfy at least one of the available contexts.
 *
 * @group joinup_core
 */
class BlockContextSatisfactionTest extends JoinupRdfBrowserTestBase {

  /**
   * An array containing the paths that contain custom Joinup code.
   *
   * All custom blocks whose definitions reside in these paths will be subjected
   * to this test.
   */
  const JOINUP_EXTENSION_PATHS = [
    'modules/custom/',
    'profiles/',
    'themes/',
  ];

  /**
   * {@inheritdoc}
   */
  protected $profile = 'joinup';

  /**
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * An array of extension lists.
   *
   * @var \Drupal\Core\Extension\ExtensionList[]
   */
  protected $extensionLists;

  /**
   * The context repository.
   *
   * @var \Drupal\Core\Plugin\Context\LazyContextRepository
   */
  protected $contextRepository;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->blockManager = $this->container->get('plugin.manager.block');
    $this->contextRepository = $this->container->get('context.repository');

    foreach (['extension.list.module', 'extension.list.profile'] as $service_id) {
      $this->extensionLists[] = $this->container->get($service_id);
    }
  }

  /**
   * Tests that our custom blocks satisfy at least one available context.
   */
  public function testBlockContextSatisfaction() {
    $satisfied_definition_ids = array_keys($this->getBlockDefinitionsFilteredByAvailableContexts());
    $joinup_definition_ids = array_keys($this->getJoinupBlockDefinitions());

    $unsatisfied_definition_ids = [];
    foreach ($joinup_definition_ids as $definition_id) {
      if (!in_array($definition_id, $satisfied_definition_ids)) {
        $unsatisfied_definition_ids[] = $definition_id;
      }
    }

    $this->assertEmpty($unsatisfied_definition_ids, 'Blocks found with invalid context definitions: ' . implode(', ', $unsatisfied_definition_ids));
  }

  /**
   * Returns the block definitions that match one or more available contexts.
   *
   * @return array[]
   *   An associative array of block definitions, keyed by block ID.
   */
  protected function getBlockDefinitionsFilteredByAvailableContexts(): array {
    $contexts = $this->contextRepository->getAvailableContexts();

    // Pretend that we are getting the list of blocks for the Block UI module.
    return $this->blockManager->getFilteredDefinitions('block_ui', $contexts, []);
  }

  /**
   * Returns the block definitions that are defined in custom Joinup code.
   *
   * @return array[]
   *   An associative array of block definitions, keyed by block ID.
   */
  protected function getJoinupBlockDefinitions(): array {
    $definitions = $this->blockManager->getDefinitions();
    $joinup_extension_ids = array_keys($this->getJoinupExtensions());

    return array_filter($definitions, function (array $definition) use ($joinup_extension_ids) {
      return in_array($definition['provider'], $joinup_extension_ids);
    });
  }

  /**
   * Returns the custom extensions that are provided by Joinup.
   *
   * @return \Drupal\Core\Extension\Extension[]
   *   The extensions.
   */
  protected function getJoinupExtensions(): array {
    $extensions = [];
    foreach ($this->extensionLists as $list) {
      $extensions += $list->getList();
    }

    $extension_paths = static::JOINUP_EXTENSION_PATHS;

    return array_filter($extensions, function (Extension $extension) use ($extension_paths) {
      foreach ($extension_paths as $extension_path) {
        if (strpos($extension->getPath(), $extension_path) === 0) {
          return TRUE;
        }
      }
      return FALSE;
    });
  }

}
