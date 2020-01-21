<?php

namespace Drupal\joinup_seo\Plugin\simple_sitemap\UrlGenerator;

use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Class JoinupEntityUrlGenerator.
 *
 * @package Drupal\joinup_seo\Plugin\simple_sitemap\UrlGenerator
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
      // Recent news entities are indexed in another sitemap. Only news that are
      // published earlier than two days ago should end up to the general index.
      $query->condition('published_at', $two_days_ago, '<');
    }
  }

}
