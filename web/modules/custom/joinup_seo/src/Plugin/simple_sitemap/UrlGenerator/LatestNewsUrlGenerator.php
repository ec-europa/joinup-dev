<?php

declare(strict_types = 1);

namespace Drupal\joinup_seo\Plugin\simple_sitemap\UrlGenerator;

use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Generates URL for recently created news to include in the sitemap.
 *
 * @UrlGenerator(
 *   id = "latest_news",
 *   label = @Translation("Latest news URL generator"),
 *   description = @Translation("Generates URLs for news created within the last 2 days."),
 * )
 */
class LatestNewsUrlGenerator extends JoinupUrlGeneratorBase {

  /**
   * {@inheritdoc}
   */
  public function alterQuery(QueryInterface $query, string $entity_type_id, string $bundle, array $bundle_settings): void {
    // It is assumed that this is called only for news and checks of validity
    // should be performed before the query object is constructed.
    $two_days_ago = strtotime('2 days ago');
    $query->condition('published_at', PUBLICATION_DATE_DEFAULT, '<');
    $query->condition('published_at', $two_days_ago, '>=');
  }

}
