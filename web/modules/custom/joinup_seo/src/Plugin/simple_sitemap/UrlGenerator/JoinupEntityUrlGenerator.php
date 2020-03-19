<?php

declare(strict_types = 1);

namespace Drupal\joinup_seo\Plugin\simple_sitemap\UrlGenerator;

use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Generates URLs to content on Joinup for inclusion in the sitemap.
 *
 * @UrlGenerator(
 *   id = "joinup_entity",
 *   label = @Translation("Joinup URL generator"),
 *   description = @Translation("Generates URLs for entities ignoring recent news."),
 * )
 */
class JoinupEntityUrlGenerator extends JoinupUrlGeneratorBase {

  /**
   * {@inheritdoc}
   */
  public function alterQuery(QueryInterface $query, string $entity_type_id, string $bundle, array $bundle_settings): void {
    if ($entity_type_id === 'node' && $bundle === 'news') {
      $two_days_ago = strtotime('2 days ago');
      // Recent news entities are indexed in a separate sitemap. Only news items
      // that have been published earlier than two days ago should end up in the
      // general index.
      $query->condition('published_at', $two_days_ago, '<');
    }
  }

}
