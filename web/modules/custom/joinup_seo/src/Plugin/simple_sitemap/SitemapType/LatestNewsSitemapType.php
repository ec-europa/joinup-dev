<?php

declare(strict_types = 1);

namespace Drupal\joinup_seo\Plugin\simple_sitemap\SitemapType;

use Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapType\SitemapTypeBase;

/**
 * A sitemap type that includes generators related to latest news.
 *
 * @SitemapType(
 *   id = "latest_news",
 *   label = @Translation("Latest news"),
 *   description = @Translation("Contains latest news articles."),
 *   sitemapGenerator = "default",
 *   urlGenerators = {
 *     "latest_news",
 *   },
 * )
 */
class LatestNewsSitemapType extends SitemapTypeBase {
}
