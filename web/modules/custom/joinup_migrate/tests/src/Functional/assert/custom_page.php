<?php

/**
 * @file
 * Assertions for 'custom_page' and 'custom_page_parent' migrations.
 */

use Drupal\node\Entity\Node;

// Migration counts.
$this->assertTotalCount('custom_page_parent', 2);
$this->assertSuccessCount('custom_page_parent', 2);
$this->assertTotalCount('custom_page', 8);
$this->assertSuccessCount('custom_page', 8);

// Parent custom pages.
/* @var \Drupal\node\NodeInterface $custom_page */
$custom_page = $this->loadEntityByLabel('node', 'Digital Signature Service', 'custom_page');
$this->assertEquals('Digital Signature Service', $custom_page->label());
$this->assertEquals('custom_page', $custom_page->bundle());
$this->assertTrue($custom_page->get('body')->isEmpty());
/* @var \Drupal\rdf_entity\RdfInterface $collection */
$collection = $this->loadEntityByLabel('rdf_entity', 'Collection with 2 entities having custom section', 'collection');
$this->assertEquals($collection->id(), $custom_page->og_audience->target_id);
/* @var \Drupal\menu_link_content\MenuLinkContentInterface $parent_link1 */
$parent_link1 = $this->loadEntityByLabel('menu_link_content', 'Digital Signature Service');
$this->assertTrue($parent_link1->isEnabled());
$this->assertEquals('internal:/' . $custom_page->toUrl()->getInternalPath(), $parent_link1->link->uri);
$this->assertRedirects([], $custom_page);
$this->assertTrue($custom_page->isPublished());

$custom_page = $this->loadEntityByLabel('node', 'European Interoperability Catalogue (EIC)', 'custom_page');
$this->assertEquals('European Interoperability Catalogue (EIC)', $custom_page->label());
$this->assertEquals('custom_page', $custom_page->bundle());
$this->assertTrue($custom_page->get('body')->isEmpty());
$this->assertEquals($collection->id(), $custom_page->og_audience->target_id);
/* @var \Drupal\menu_link_content\MenuLinkContentInterface $parent_link2 */
$parent_link2 = $this->loadEntityByLabel('menu_link_content', 'European Interoperability Catalogue (EIC)');
$this->assertTrue($parent_link2->isEnabled());
$this->assertEquals('internal:/' . $custom_page->toUrl()->getInternalPath(), $parent_link2->link->uri);
$this->assertRedirects([], $custom_page);
$this->assertTrue($custom_page->isPublished());

// Children custom pages.
$custom_page = Node::load(74530);
$this->assertEquals('Roadmap 2016', $custom_page->label());
$this->assertEquals('custom_page', $custom_page->bundle());
$this->assertEquals(1389264097, $custom_page->created->value);
$this->assertContains('An indicative timeline is provided below, with planned', $custom_page->get('body')->value);
$this->assertEquals($collection->id(), $custom_page->og_audience->target_id);
/* @var \Drupal\menu_link_content\MenuLinkContentInterface $link */
$link = $this->loadEntityByLabel('menu_link_content', 'Roadmap 2016');
$this->assertTrue($link->isEnabled());
$this->assertEquals('internal:/' . $custom_page->toUrl()->getInternalPath(), $link->link->uri);
$this->assertEquals('menu_link_content:' . $parent_link1->uuid(), $link->getParentId());
$this->assertRedirects(['asset/sd-dss/og_page/roadmap-2016'], $custom_page);
$this->assertTrue($custom_page->isPublished());

$custom_page = Node::load(157896);
$this->assertEquals('Solutions per domain', $custom_page->label());
$this->assertEquals('custom_page', $custom_page->bundle());
$this->assertEquals(1482238075, $custom_page->created->value);
$this->assertContains('quality solutions that are relevant for public administrations and can be used to provide EU public', $custom_page->get('body')->value);
$this->assertEquals($collection->id(), $custom_page->og_audience->target_id);
$link = $this->loadEntityByLabel('menu_link_content', 'Solutions per domain');
$this->assertTrue($link->isEnabled());
$this->assertEquals('internal:/' . $custom_page->toUrl()->getInternalPath(), $link->link->uri);
$this->assertEquals('menu_link_content:' . $parent_link2->uuid(), $link->getParentId());
$this->assertRedirects(['community/eic/og_page/solutions-domain'], $custom_page);
$this->assertTrue($custom_page->isPublished());

$custom_page = Node::load(157897);
$this->assertEquals('Eligibility criteria', $custom_page->label());
$this->assertEquals('custom_page', $custom_page->bundle());
$this->assertEquals(1482238234, $custom_page->created->value);
$this->assertContains('This table summarises the scoping criteria that will allow', $custom_page->get('body')->value);
$this->assertEquals($collection->id(), $custom_page->og_audience->target_id);
$link = $this->loadEntityByLabel('menu_link_content', 'Eligibility criteria');
$this->assertFalse($link->isEnabled());
$this->assertEquals('internal:/' . $custom_page->toUrl()->getInternalPath(), $link->link->uri);
$this->assertEquals('menu_link_content:' . $parent_link2->uuid(), $link->getParentId());
$this->assertRedirects(['community/eic/og_page/eligibility-criteria'], $custom_page);
$this->assertTrue($custom_page->isPublished());

// Orphan custom pages. We test only 1 of 5.
$custom_page = Node::load(74988);
$this->assertEquals('CAMSS Tools', $custom_page->label());
$this->assertEquals('custom_page', $custom_page->bundle());
$this->assertEquals(1390378400, $custom_page->created->value);
$this->assertContains('An assessment of a technical specification or a standard for adoption by public administrations', $custom_page->get('body')->value);
$collection = $this->loadEntityByLabel('rdf_entity', 'Collection with 1 entity having custom section', 'collection');
$this->assertEquals($collection->id(), $custom_page->og_audience->target_id);
$link = $this->loadEntityByLabel('menu_link_content', 'CAMSS Tools');
$this->assertTrue($link->isEnabled());
$this->assertEquals('internal:/' . $custom_page->toUrl()->getInternalPath(), $link->link->uri);
$this->assertEmpty($link->getParentId());
$this->assertRedirects(['community/camss/og_page/camss-tools'], $custom_page);
$this->assertFalse($custom_page->isPublished());
