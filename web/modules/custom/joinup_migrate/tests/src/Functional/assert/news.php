<?php

/**
 * @file
 * Assertions for 'news' migration.
 */

// Migration counts.
$this->assertTotalCount('news', 3);
$this->assertSuccessCount('news', 3);

// Imported content check.
/* @var \Drupal\node\NodeInterface $news */
$news = $this->loadEntityByLabel('node', 'Mobile Age project: Co-created personalised mobile access to public services for senior citizens – 2nd Newsletter Issue now available!');
$this->assertEquals('Mobile Age project: Co-created personalised mobile access to public services for senior citizens – 2nd Newsletter Issue now available!', $news->label());
$this->assertEquals('Mobile Age project: Co-created personalised mobile access to public services for senior citizens – 2nd Newsletter Issue now available!', $news->field_news_headline->value);
$this->assertEquals('news', $news->bundle());
$this->assertEquals(1475759242, $news->created->value);
$this->assertEquals(1475763134, $news->changed->value);
$this->assertEquals(1, $news->uid->target_id);
$this->assertEquals('http://www.mobile-age.eu/newsletters-issues/newsletter-issue-no-2-october-2016.html', $news->field_news_source_url->uri);
$this->assertStringEndsWith("<p>City/Location: Athens</p>\n", $news->body->value);
$this->assertKeywords([], $news);
$this->assertReferences(static::$europeCountries, $news->field_news_spatial_coverage);
$this->assertEquals($new_collection->id(), $news->og_audience->target_id);
$this->assertEquals('validated', $news->field_state->value);
$this->assertRedirects(['news/mobile-age-project-co-created-personalised-mobile-access-public-services-senior-citizens-–-2nd-'], $news);

$news = $this->loadEntityByLabel('node', 'BE, NL: governments will not use ISO OOXML');
$this->assertEquals('BE, NL: governments will not use ISO OOXML', $news->label());
$this->assertEquals('BE, NL: governments will not use ISO OOXML', $news->field_news_headline->value);
$this->assertEquals('news', $news->bundle());
$this->assertEquals(1207612800, $news->created->value);
$this->assertEquals(1455199428, $news->changed->value);
$this->assertEquals(1, $news->uid->target_id);
$this->assertTrue($news->get('field_news_source_url')->isEmpty());
$this->assertStringEndsWith("<a href=\"http://www.pcworld.com/article/id,144036-pg,1/article.html\">IDG News item</a></div>\r\n\t</li>\r\n</ul>\r\n", $news->body->value);
$this->assertKeywords([
  '[GL] Belgium',
  '[GL] The Netherlands',
  '[T] Policies and Announcements',
], $news);
$this->assertReferences(['Belgium', 'Netherlands'], $news->field_news_spatial_coverage);
$this->assertEquals($new_collection->id(), $news->og_audience->target_id);
$this->assertEquals('validated', $news->field_state->value);
$this->assertRedirects(['osor/news/be-nl-governments-will-not-use-iso-ooxml'], $news);

$news = $this->loadEntityByLabel('node', 'Public workshop to discuss ways to sustain governmental open standards', 'news');
$this->assertEquals('Public workshop to discuss ways to sustain governmental open standards', $news->label());
$this->assertEquals('Public workshop to discuss ways to sustain governmental open standards', $news->field_news_headline->value);
$this->assertEquals('news', $news->bundle());
$this->assertEquals(1366966462, $news->created->value);
$this->assertEquals(1366966650, $news->changed->value);
$this->assertEquals(1, $news->uid->target_id);
$this->assertTrue($news->get('field_news_source_url')->isEmpty());
$this->assertStringEndsWith("Bomos2i (English, pdf)</a><br />\r\n\t<a href=\"http://www.logius.nl/\" target=\"_blank\">Logius</a></p>\r\n", $news->body->value);
$this->assertTrue($news->get('field_keywords')->isEmpty());
$this->assertReferences(['Netherlands', 'European Union'], $news->field_news_spatial_coverage);
$collection = $this->loadEntityByLabel('rdf_entity', 'Archived collection', 'collection');
$this->assertEquals($collection->id(), $news->og_audience->target_id);
$this->assertEquals('validated', $news->field_state->value);
$this->assertRedirects(['community/osor/news/public-workshop-discuss-ways-sustain-governmental-open-standards'], $news);
