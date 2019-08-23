<?php

namespace Drupal\Tests\custom_page\Kernel;

use Drupal\Core\Serialization\Yaml;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\og_menu\Entity\OgMenu;
use Drupal\og_menu\Entity\OgMenuInstance;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\Entity\RdfEntityType;
use Drupal\sparql_entity_storage\Entity\SparqlMapping;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;

/**
 * Tests the custom page OG menu link updater service.
 *
 * @group custom_page
 */
class CustomPageOgMenuLinksManagerTest extends KernelTestBase {

  use SparqlConnectionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'custom_page',
    'field',
    'language',
    'link',
    'menu_link_content',
    'menu_ui',
    'node',
    'og',
    'og_menu',
    'og_ui',
    'rdf_entity',
    'rdf_schema_field_validation',
    'sparql_entity_storage',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->setUpSparql();
    $this->installConfig([
      'language',
      'og',
      'og_menu',
      'rdf_entity',
      'sparql_entity_storage',
    ]);
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('ogmenu');
    $this->installEntitySchema('ogmenu_instance');
    $this->installEntitySchema('menu_link_content');
    $this->installSchema('node', ['node_access']);

    FieldStorageConfig::create([
      'type' => 'og_standard_reference',
      'entity_type' => 'ogmenu_instance',
      'field_name' => OgGroupAudienceHelperInterface::DEFAULT_FIELD,
      'settings' => ['target_type' => 'rdf_entity'],
    ])->save();
    // Create the 'navigation' OG menu.
    OgMenu::create([
      'id' => 'navigation',
      'label' => 'Navigation',
    ])->save();

    // We don't install the original 'collection' RDF entity and 'custom_page'
    // node bundle definitions and fields because we want to avoid installing
    // the whole list of dependencies. Thus we create only simplified versions
    // of both bundles for the purpose of this test.
    $mocked_collection_type = RdfEntityType::create([
      'rid' => 'collection',
      'name' => 'Mocked collection',
    ]);
    // @see og_ui_entity_type_save().
    $mocked_collection_type->og_is_group = TRUE;
    $mocked_collection_type->og_group_content_bundle = FALSE;
    $mocked_collection_type->save();

    // Create the corresponding mapping config entity.
    $mapping_values = Yaml::decode(file_get_contents(__DIR__ . '/../../../../collection/config/install/sparql_entity_storage.mapping.rdf_entity.collection.yml'));
    SparqlMapping::create($mapping_values)
      // Don't care about the 'draft' graph.
      ->unsetGraphs(['draft'])
      ->save();

    $mocked_custom_page_type = NodeType::create([
      'type' => 'custom_page',
      'name' => 'Mocked custom page',
    ]);
    // @see og_ui_entity_type_save().
    $mocked_custom_page_type->og_group_content_bundle = TRUE;
    $mocked_custom_page_type->og_is_group = FALSE;
    $mocked_custom_page_type->og_target_type = 'rdf_entity';
    $mocked_custom_page_type->og_target_bundles = ['collection'];
    $mocked_custom_page_type->save();
  }

  /**
   * Tests the custom page OG menu link updater service.
   */
  public function testCustomPageOgMenuLinkUpdater() {
    // Create 2 collections.
    $collection_ids = $ogmenu_instance_ids = [];
    foreach ([1, 2] as $index) {
      Rdf::create([
        'rid' => 'collection',
        'id' => $collection_ids[$index] = "http://example.com/$index",
        'label' => $this->randomString(),
      ])->save();
      // Create also the paired OG menu link instance. Normally this is
      // automatically accomplished by the 'joinup_core' module but we don't
      // want to enable that module due to its complex dependencies.
      // @see joinup_core_rdf_entity_insert()
      $ogmenu_instance = OgMenuInstance::create([
        'type' => 'navigation',
        OgGroupAudienceHelperInterface::DEFAULT_FIELD => $collection_ids[$index],
      ]);
      $ogmenu_instance->save();
      $ogmenu_instance_ids[$index] = $ogmenu_instance->id();
    }

    // Create a custom page in the first collection.
    /** @var \Drupal\node\NodeInterface $custom_page */
    $custom_page = Node::create([
      'type' => 'custom_page',
      'title' => $this->randomString(),
      'og_audience' => $collection_ids[1],
    ]);
    $custom_page->save();

    // Check that a corresponding menu link has been created.
    $this->assertMenuLink($ogmenu_instance_ids[1], $custom_page->id());

    // Move the custom page in the 2nd collection.
    $custom_page->set('og_audience', $collection_ids[2])->save();

    // Check that the menu link has been removed from the 1st collection.
    $this->assertNotMenuLink($ogmenu_instance_ids[1], $custom_page->id());
    // Check that a menu link has been moved in the 2nd collection.
    $this->assertMenuLink($ogmenu_instance_ids[2], $custom_page->id());
  }

  /**
   * Asserts that a menu link exists.
   *
   * @param int $og_menu_instance_id
   *   The OG menu instance ID.
   * @param int $nid
   *   The targeted custom page node ID.
   */
  protected function assertMenuLink($og_menu_instance_id, $nid) {
    $this->assertNotEmpty($this->assertMenuLinkHelper($og_menu_instance_id, $nid));
  }

  /**
   * Asserts that a menu link doesn't exist.
   *
   * @param int $og_menu_instance_id
   *   The OG menu instance ID.
   * @param int $nid
   *   The targeted custom page node ID.
   */
  protected function assertNotMenuLink($og_menu_instance_id, $nid) {
    $this->assertEmpty($this->assertMenuLinkHelper($og_menu_instance_id, $nid));
  }

  /**
   * Provides a helper for menu links assertions.
   *
   * @param int $og_menu_instance_id
   *   The OG menu instance ID.
   * @param int $nid
   *   The targeted custom page node ID.
   *
   * @return \Drupal\menu_link_content\MenuLinkContentInterface[]
   *   A list of menu link content entities.
   */
  protected function assertMenuLinkHelper($og_menu_instance_id, $nid) {
    $properties = [
      'bundle' => 'menu_link_content',
      'menu_name' => "ogmenu-{$og_menu_instance_id}",
      'link__uri' => "entity:node/$nid",
    ];
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('menu_link_content');
    return $storage->loadByProperties($properties);
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    // Clean up the RDF entities that were created during the test. We clean
    // them up here instead of at the end of the test so that this always
    // happens, even if the test fails.
    foreach ([1, 2] as $index) {
      Rdf::load("http://example.com/$index")->delete();
    }
    parent::tearDown();
  }

}
