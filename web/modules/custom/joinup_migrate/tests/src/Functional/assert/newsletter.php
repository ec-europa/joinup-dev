<?php

/**
 * @file
 * Assertions for 'newsletter' migration.
 */

// Migration counts.
$this->assertTotalCount('newsletter', 1);
$this->assertSuccessCount('newsletter', 1);

// Imported content check.
/* @var \Drupal\node\NodeInterface $newsletter */
$newsletter = $this->loadEntityByLabel('node', 'Joinup Open Source News Service - June 2016');
$this->assertEquals('Joinup Open Source News Service - June 2016', $newsletter->label());
$this->assertEquals('newsletter', $newsletter->bundle());
$this->assertEquals(1465386690, $newsletter->created->value);
$this->assertEquals(1465464416, $newsletter->changed->value);
$user = user_load_by_name('joinup_editor');
$this->assertEquals($user->id(), $newsletter->uid->target_id);
$this->assertStringEndsWith("position of the European Union.</p>\r\n\t\t\t\t\t\t\t\t</div>\r\n\t\t\t\t\t\t\t</div>\r\n\t\t\t\t\t\t</div>\r\n\t\t\t\t\t</div>\r\n\t\t\t\t</div>\r\n\t\t\t</div>\r\n\t\t</div>\r\n\t</div>\r\n</div>\r\n<p>&nbsp;</p>\r\n", $newsletter->body->value);
