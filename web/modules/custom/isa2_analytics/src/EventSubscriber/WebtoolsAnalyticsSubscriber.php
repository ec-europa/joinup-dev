<?php

declare(strict_types = 1);

namespace Drupal\isa2_analytics\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\oe_webtools_analytics\AnalyticsEventInterface;
use Drupal\oe_webtools_analytics\Event\AnalyticsEvent;
use Drupal\og\OgContextInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Subscriber that acts on visitor analytics being collected for reporting.
 */
class WebtoolsAnalyticsSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * The OG context service.
   *
   * @var \Drupal\og\OgContextInterface
   */
  protected $ogContext;

  /**
   * Constructs a new WebtoolsAnalyticsSubscriber.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The http request stack.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $routeMatch
   *   The current route match.
   * @param \Drupal\og\OgContextInterface $ogContext
   *   The OG context service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, RequestStack $requestStack, CurrentRouteMatch $routeMatch, OgContextInterface $ogContext) {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentRequest = $requestStack->getCurrentRequest();
    $this->routeMatch = $routeMatch;
    $this->ogContext = $ogContext;
  }

  /**
   * Sets the site section on the analytics event.
   *
   * If the current page belongs to a certain group (collection or solution)
   * then this will be reported to Webtools Analytics as a "site section". This
   * allows the visitor data to be analysed for specific collections and
   * solutions.
   *
   * @param \Drupal\oe_webtools_analytics\AnalyticsEventInterface $event
   *   Response event.
   */
  public function setSiteSection(AnalyticsEventInterface $event) {
    // The site section varies by the active group.
    $event->addCacheContexts(['og_group_context']);

    // Set the group label as the site section if there is an active group.
    if ($group = $this->ogContext->getGroup()) {
      if ($group->bundle() === 'solution') {

        $collection = NULL;
        // Normally, the solution has the `collections` computed field that
        // holds the list of collections that are affiliates to the solution.
        // However, this method is fired during the preparation of the page
        // while the computation of the value takes place during the rendering
        // of the page.
        // @see \Drupal\Core\Render\MainContent\HtmlRenderer::renderResponse
        if ($collections = solution_get_collection_ids($group)) {
          // Only the first affiliated collection should be used to set the site
          // section. This is considered to be the "main" collection. Any other
          // collections are ignored since they would make the analytics results
          // more difficult to interpret.
          $collection_id = reset($collections);
          $collection = $this->entityTypeManager->getStorage('rdf_entity')->load($collection_id);
        }
        $group = $collection;
      }
    }

    if (!empty($group)) {
      $event->setSiteSection($group->id());
      $event->addCacheableDependency($group);
    }
  }

  /**
   * Sets the webtools search data in the MATOMO json.
   *
   * @param \Drupal\oe_webtools_analytics\AnalyticsEventInterface $event
   *   Response event.
   */
  public function setSearchData(AnalyticsEventInterface $event) {
    if ($this->routeMatch->getRouteName() !== 'view.search.page_1') {
      return;
    }

    if ($keys = $this->currentRequest->get('keys')) {
      $search_data = $event->getSearch();
      $search_data->setKeyword($keys);
      if ($total_rows = $this->currentRequest->attributes->get('total_rows')) {
        $search_data->setCount($total_rows);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AnalyticsEvent::NAME][] = ['setSiteSection'];
    $events[AnalyticsEvent::NAME][] = ['setSearchData'];

    return $events;
  }

}
