<?php

/**
 * @file
 * Assertions for 'reference' migration.
 *
 * What we test? In the body field of source node 76890 there are several links
 * that must be correctly replaced. See documentation on each test case.
 */

use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;

/* @var \Drupal\Core\Path\AliasManagerInterface $alias_manager */
$alias_manager = \Drupal::service('path.alias_manager');
$sql = "SELECT body FROM {node_revisions} WHERE nid = :nid ORDER BY vid DESC";
$node = Node::load(76890);
// This file is referenced as file system URL in the body of node 76890.
$file = File::load(30186);
/* @var \Drupal\rdf_entity\RdfInterface $collection */
$collection = $this->loadEntityByLabel('rdf_entity', 'Collection from Community', 'collection');
$collection_aliased_link = $alias_manager->getAliasByPath('/' . $collection->toUrl()->getInternalPath());
/* @var \Drupal\node\NodeInterface $event */
$event = Node::load(150255);
$event_aliased_link = $alias_manager->getAliasByPath('/' . $event->toUrl()->getInternalPath());

$text_before_migration = $this->legacyDb->queryRange($sql, 0, 1, [':nid' => 76890])->fetchField();
$text_after_migration = $node->body->value;

// Case 1: Link to a file on the file-system:
// D6 https://joinup.ec.europa.eu/sites/default/files/list_of_standards_v1.1.xls
// D8 /sites/default/files/custom-page/attachment/list_of_standards_v1.1.xls
// Before.
$this->assertContains('https://joinup.ec.europa.eu/sites/default/files/list_of_standards_v1.1.xls', $text_before_migration);
$this->assertNotContains('/sites/default/files/custom-page/attachment/list_of_standards_v1.1.xls', $text_before_migration);
$this->assertNotContains($file->uuid(), $text_before_migration);
// After.
$this->assertNotContains('https://joinup.ec.europa.eu/sites/default/files/list_of_standards_v1.1.xls', $text_after_migration);
$this->assertContains('/sites/default/files/custom-page/attachment/list_of_standards_v1.1.xls', $text_after_migration);
$this->assertContains($file->uuid(), $text_after_migration);

// Case 2: Canonical link to a node that creates a Drupal 8 collection:
// D6 /node/149141
// D8 /collection/collection-community
// Before.
$this->assertContains('/node/149141', $text_before_migration);
// After.
$this->assertNotContains('/node/149141', $text_after_migration);

// Case 3: Aliased link of the same node that creates a Drupal 8 collection:
// D6 /community/edp/description
// D8 /collection/collection-community
// Before.
$this->assertContains('/community/edp/description', $text_before_migration);
$this->assertNotContains($collection_aliased_link, $text_before_migration);
// After.
$this->assertNotContains('/community/edp/description', $text_after_migration);
$this->assertContains($collection_aliased_link, $text_after_migration);

// Case 4: Community content canonical link is preserved:
// D6 /node/150255
// D8 /node/150255
// Before.
$this->assertContains('/node/150255', $text_before_migration);
// After.
$this->assertContains('/node/150255', $text_after_migration);

// Case 5: Alias of the same community content preserves the node ID but changes
// the link into its Drupal 8 alias:
// D6 /asset/cpsv-ap/event/cpsv-ap-revision-wg-virtual-meeting-0
// D8 /event/cpsv-ap-revision-wg-virtual-meeting-3
// Before.
$this->assertContains('/asset/cpsv-ap/event/cpsv-ap-revision-wg-virtual-meeting-0', $text_before_migration);
// After.
$this->assertContains($event_aliased_link, $text_after_migration);
$this->assertNotContains('/asset/cpsv-ap/event/cpsv-ap-revision-wg-virtual-meeting-0', $text_after_migration);
$this->assertContains($event->uuid(), $text_after_migration);

// Case 6: User canonical link is preserved:
// D6 /user/6481
// D8 /user/6481
// Before.
$this->assertContains('/user/6481', $text_before_migration);
// After.
$this->assertContains('/user/6481', $text_after_migration);

// Case 7: User aliased link is transformed into user Drupal 8 alias:
// D6 /people/6481
// D8 /user/6481 (there's no Pathauto policy yet for users)
// Before.
$this->assertContains('/people/6481', $text_before_migration);
// After.
$this->assertContains('/user/6481', $text_after_migration);

// Case 8: User 'content' profile node is transformed into user Drupal 8 alias:
// D6 /node/20226
// D8 /user/6481 (there's no Pathauto policy yet for users)
// Before.
$this->assertContains('/node/20226', $text_before_migration);
// After.
$this->assertNotContains('/node/20226', $text_after_migration);

// Case 9: User aliased 'content' profile node is transformed into user Drupal 8
// alias:
// D6 /profile/afalciano-profile
// D8 /user/6481 (there's no Pathauto policy yet for users)
// Before.
$this->assertContains('/profile/afalciano-profile', $text_before_migration);
// After.
$this->assertNotContains('/profile/afalciano-profile', $text_after_migration);

// Case 10: An absolute link to a 'discussion', with fragment, is is transformed
// into the migrated discussion alias and the fragment is appended:
// D6 https://joinup.ec.europa.eu/asset/core_location/issue/related-work-open-geospatial-consortium#some-fragment
// D8 /discussion/related-work-open-geospatial-consortium#some-fragment
// Before.
$this->assertContains('https://joinup.ec.europa.eu/asset/core_location/issue/related-work-open-geospatial-consortium#some-fragment', $text_before_migration);
// After.
$this->assertContains('/discussion/related-work-open-geospatial-consortium#some-fragment', $text_after_migration);
$this->assertNotContains('https://joinup.ec.europa.eu/asset/core_location/issue/related-work-open-geospatial-consortium#some-fragment', $text_after_migration);
