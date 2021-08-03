<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_eulogin\ExistingSite;

use Drupal\Tests\joinup_test\ExistingSite\JoinupExistingSiteTestBase;
use Drupal\Tests\rdf_entity\Traits\DrupalTestTraits\RdfEntityCreationTrait;

/**
 * Tests the 403 redirection cache.
 *
 * After an unpublished entity is saved, an anonymous, trying to access the
 * entity's canonical URL should receive am access denied (403) response. But,
 * the 'joinup_eulogin.anonymous_403.subscriber' subscriber redirects all
 * anonymous access denied pages pages to EU Login login page. This redirect
 * response is cached. This test makes sure that the redirect cache is correctly
 * invalidated when the entity visibility changes.
 *
 * @group joinup_eulogin
 *
 * @see \Drupal\joinup_eulogin\Event\Subscriber\JoinupEuLoginAnonymous403Subscriber
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

    $community = $this->createRdfEntity([
      'rid' => 'collection',
      'field_ar_state' => 'draft',
      'title' => $this->randomString(),
    ]);
    $community->save();
    $entities['field_ar_state'] = $community;

    $solution = $this->createRdfEntity([
      'rid' => 'solution',
      'field_is_state' => 'draft',
      'title' => $this->randomString(),
      'collection' => $community,
    ]);
    $solution->save();
    $entities['field_is_state'] = $solution;

    $release = $this->createRdfEntity([
      'rid' => 'asset_release',
      'field_isr_state' => 'draft',
      'title' => $this->randomString(),
      'field_isr_is_version_of' => $solution,
    ]);
    $release->save();
    $entities['field_isr_state'] = $release;

    $custom_page = $this->createNode([
      'type' => 'custom_page',
      'status' => FALSE,
      'title' => $this->randomString(),
      'og_audience' => $community,
    ]);
    $custom_page->save();
    $entities['status'] = $custom_page;

    $discussion = $this->createNode([
      'type' => 'discussion',
      'field_state' => 'draft',
      'title' => $this->randomString(),
      'og_audience' => $community,
    ]);
    $discussion->save();
    $entities['field_state'] = $discussion;

    $document = $this->createNode([
      'type' => 'document',
      'field_state' => 'draft',
      'title' => $this->randomString(),
      'og_audience' => $community,
    ]);
    $document->save();
    $entities['field_state'] = $document;

    $event = $this->createNode([
      'type' => 'event',
      'field_state' => 'draft',
      'title' => $this->randomString(),
      'og_audience' => $community,
    ]);
    $event->save();
    $entities['field_state'] = $event;

    $news = $this->createNode([
      'type' => 'news',
      'field_state' => 'draft',
      'title' => $this->randomString(),
      'og_audience' => $community,
    ]);
    $news->save();
    $entities['field_state'] = $news;

    return $entities;
  }

}
