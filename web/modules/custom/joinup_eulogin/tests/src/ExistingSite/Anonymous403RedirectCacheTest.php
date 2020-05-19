<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_eulogin\ExistingSite;

use Drupal\Tests\joinup_test\ExistingSite\JoinupExistingSiteTestBase;
use Drupal\Tests\rdf_entity\Traits\DrupalTestTraits\RdfEntityCreationTrait;

/**
 * Tests the 403 redirection cache.
 *
 * @group joinup_eulogin
 */
class Anonymous403RedirectCacheTest extends JoinupExistingSiteTestBase {

  use RdfEntityCreationTrait;

  /**
   * Tests the 403 redirection cache.
   */
  public function testRedirectCache(): void {
    foreach ($this->getEntities() as $field_name => $entity) {
      // Visit the unpublished entity to warm-up the redirect cache.
      $this->drupalGet($entity->toUrl());
      $this->assertSession()->pageTextContains('Sign in to continue');

      // Publish the entity.
      $value = $entity->bundle() === 'custom_page' ? TRUE : 'validated';
      $entity->set($field_name, $value)->save();

      // Revisit the entity. It should be visible.
      $this->drupalGet($entity->toUrl());
      $this->assertSession()->pageTextNotContains('Sign in to continue');
      $this->assertSession()->pageTextContains($entity->label());
      $this->assertSession()->addressEquals($entity->toUrl());
    }
  }

  /**
   * Returns a list of testing entities.
   *
   * @return array
   *   An associative array of entities keyed by the state/status field.
   */
  protected function getEntities(): array {
    $entities = [];

    $collection = $this->createRdfEntity([
      'rid' => 'collection',
      'field_ar_state' => 'draft',
      'title' => $this->randomString(),
    ]);
    $collection->save();
    $entities['field_ar_state'] = $collection;

    $custom_page = $this->createNode([
      'type' => 'custom_page',
      'status' => FALSE,
      'title' => $this->randomString(),
      'og_audience' => $collection,
    ]);
    $custom_page->save();
    $entities['status'] = $custom_page;

    $discussion = $this->createNode([
      'type' => 'discussion',
      'field_state' => 'draft',
      'title' => $this->randomString(),
      'og_audience' => $collection,
    ]);
    $discussion->save();
    $entities['field_state'] = $discussion;

    $document = $this->createNode([
      'type' => 'document',
      'field_state' => 'draft',
      'title' => $this->randomString(),
      'og_audience' => $collection,
    ]);
    $document->save();
    $entities['field_state'] = $document;

    $event = $this->createNode([
      'type' => 'event',
      'field_state' => 'draft',
      'title' => $this->randomString(),
      'og_audience' => $collection,
    ]);
    $event->save();
    $entities['field_state'] = $event;

    $news = $this->createNode([
      'type' => 'news',
      'field_state' => 'draft',
      'title' => $this->randomString(),
      'og_audience' => $collection,
    ]);
    $news->save();
    $entities['field_state'] = $news;

    return $entities;
  }

}
