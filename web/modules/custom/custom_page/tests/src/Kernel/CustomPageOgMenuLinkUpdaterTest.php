<?php

namespace Drupal\Tests\custom_page\Kernel;

use Drupal\Core\Serialization\Yaml;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\og_menu\Entity\OgMenu;
use Drupal\og_menu\Entity\OgMenuInstance;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\Entity\RdfEntityType;
use Drupal\Tests\rdf_entity\Traits\RdfDatabaseConnectionTrait;

/**
 * Tests the custom page OG menu link updater service.
 *
 * @group custom_page
 */
class CustomPageOgMenuLinkUpdaterTest extends KernelTestBase {

  use RdfDatabaseConnectionTrait;

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
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->setUpSparql();
    $this->installConfig(['og', 'language']);
    $this->installConfig(['og_menu']);
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
    $rdf_entity_3rd_party = Yaml::decode(file_get_contents(__DIR__ . '/../../../../collection/config/install/rdf_entity.rdfentity.collection.yml'))['third_party_settings']['rdf_entity'];
    foreach ($rdf_entity_3rd_party as $key => $value) {
      $mocked_collection_type->setThirdPartySetting('rdf_entity', $key, $value);
    }
    $mocked_collection_type->save();

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
      $ogmenu_instance = OgMenuInstance::create([
        'type' => 'navigation',
        OgGroupAudienceHelperInterface::DEFAULT_FIELD => $collection_ids[$index],
      ]);
      $ogmenu_instance->save();
      $ogmenu_instance_ids[$index] = $ogmenu_instance->id();
    }

    // Create a custom page in the first collection.
    $custom_page = Node::create([
      'type' => 'custom_page',
      'title' => $this->randomString(),
      'og_audience' => $collection_ids[1],
    ]);
    $custom_page->save();

    // Check that a corresponding menu link has been created.
    $link = $this->assertMenuLink($ogmenu_instance_ids[1], $custom_page->id());

    // Create a 2nd custom page as child of the first.
    $child_custom_page = Node::create([
      'type' => 'custom_page',
      'title' => $this->randomString(),
      'og_audience' => $collection_ids[1],
    ]);
    $child_custom_page->save();

    // Check that a corresponding menu link has been created.
    $child_link = $this->assertMenuLink($ogmenu_instance_ids[1], $child_custom_page->id());
    $child_link->set('parent', "menu_link_content:{$link->uuid()}")->save();

    // Move the child custom page to the 2nd collection.
    $child_custom_page->set('og_audience', $collection_ids[2])->save();

    // Check that the menu link has been removed from the 1st collection.
    $this->assertNotMenuLink($ogmenu_instance_ids[1], $child_custom_page->id());
    // Check that a menu link has been created in the 2nd collection.
    $child_link = $this->assertMenuLink($ogmenu_instance_ids[2], $child_custom_page->id());

    // Move back the child custom page to the 1nd collection.
    $child_custom_page->set('og_audience', $collection_ids[1])->save();

    // Check that the menu link has been removed from the 2st collection.
    $this->assertNotMenuLink($ogmenu_instance_ids[2], $child_custom_page->id());
    // Check that a menu link has been created in the 1nd collection.
    $child_link = $this->assertMenuLink($ogmenu_instance_ids[1], $child_custom_page->id());
    // Restore the parent relation.
    $child_link->set('parent', "menu_link_content:{$link->uuid()}")->save();

    // Move the parent custom page to the 2nd collection.
    $custom_page->set('og_audience', $collection_ids[2])->save();

    // Check that the menu link has been removed from the 1st collection.
    $this->assertNotMenuLink($ogmenu_instance_ids[1], $custom_page->id());
    // Check that a menu link has been created in the 2nd collection.
    $this->assertMenuLink($ogmenu_instance_ids[2], $custom_page->id());

    // Check that the child custom page parent relation has been removed.
    $child_link = MenuLinkContent::load($child_link->id());
    $this->assertTrue($child_link->get('parent')->isEmpty());
  }

  /**
   * Asserts that a menu link exists.
   *
   * @param int $og_menu_instance_id
   *   The OG menu instance ID.
   * @param int $nid
   *   The targeted custom page node ID.
   * @param int|null $occurrences
   *   (optional) Number of expected occurrences. If not passed, this test will
   *   assume only one occurence.
   *
   * @return \Drupal\menu_link_content\MenuLinkContentInterface|null
   *   The menu link content entity or NULL.
   */
  protected function assertMenuLink($og_menu_instance_id, $nid, $occurrences = 1) {
    $properties = [
      'bundle' => 'menu_link_content',
      'menu_name' => "ogmenu-{$og_menu_instance_id}",
      'link__uri' => "internal:/node/$nid",
    ];
    $menu_link_content_storage = $this->container->get('entity_type.manager')
      ->getStorage('menu_link_content');
    $links = $menu_link_content_storage->loadByProperties($properties);
    $this->assertNotEmpty($links);
    $this->assertCount($occurrences, $links);
    return reset($links);
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
    $properties = [
      'bundle' => 'menu_link_content',
      'menu_name' => "ogmenu-{$og_menu_instance_id}",
      'link__uri' => "internal:/node/$nid",
    ];
    $menu_link_content_storage = $this->container->get('entity_type.manager')
      ->getStorage('menu_link_content');
    $links = $menu_link_content_storage->loadByProperties($properties);
    $this->assertEmpty($links);
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    foreach ([1, 2] as $index) {
      Rdf::load("http://example.com/$index")->delete();
    }
  }

}
