<?php

declare(strict_types = 1);

/**
 * @file
 * Listening to the AnalyticsEvent.
 */

namespace Drupal\isa2_analytics\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\oe_webtools_analytics\AnalyticsEventInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\oe_webtools_analytics\Event\AnalyticsEvent;

/**
 * Event Subscriber AnalyticsEventSubscriber.
 */
class WebtoolsAnalyticsSubscriber implements EventSubscriberInterface {
  /**
   * The Configuration overrides instance.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $config;

  /**
   * {@inheritdoc}
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  private $logger;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The context handler service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * AnalyticsEventSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request on the stack.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The context handler service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestStack $request_stack, LoggerChannelFactoryInterface $logger_factory, EntityTypeManagerInterface $entity_type_manager, ContextRepositoryInterface $context_repository) {
    // Get id from settings.php!
    $this->config = $config_factory->get(AnalyticsEventInterface::CONFIG_NAME);
    $this->requestStack = $request_stack;
    $this->logger = $logger_factory->get('oe_webtools');
    $this->entityTypeManager = $entity_type_manager;
    $this->contextRepository = $context_repository;

  }

  /**
   * Kernel request event handler.
   *
   * @param \Drupal\oe_webtools_analytics\AnalyticsEventInterface $event
   *   Response event.
   */
  public function setSiteSection(AnalyticsEventInterface $event) {
    $event->addCacheableDependency($this->config);
    $event->addCacheContexts(['url.path']);

    $runtime_contexts = $this->contextRepository->getRuntimeContexts(['@og.context:og']);
    if (!isset($runtime_contexts['@og.context:og'])) {
      return;
    }
    /** @var \Drupal\rdf_entity\RdfInterface $group */
    $group = $runtime_contexts['@og.context:og']->getContextValue();
    if (empty($group)) {
      return;
    }

    if ($group->bundle() === 'collection') {
      $site_section_group = $group;
    }
    elseif ($group->bundle() === 'solution') {
      $affiliations = solution_get_collection_ids($group);
      if (!empty($affiliations)) {
        $site_section_group = $this->entityTypeManager->getStorage('rdf_entity')->load(reset($affiliations));
      }
    }

    if (isset($site_section_group)) {
      $event->setSiteSection($site_section_group->id());
      $event->addCacheableDependency($site_section_group);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Subscribing to listening to the Analytics event.
    $events[AnalyticsEvent::NAME][] = ['setSiteSection'];

    return $events;
  }

}
