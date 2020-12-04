<?php

declare(strict_types = 1);

namespace Drupal\asset_distribution\EventSubscriber;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\joinup_group\Event\AddGroupContentEvent;
use Drupal\joinup_group\EventSubscriber\AddGroupContentEventSubscriberBase;
use Drupal\solution\Entity\SolutionInterface;

/**
 * Subscribes to Joinup Group events.
 */
class DistributionGroupSubscriber extends AddGroupContentEventSubscriberBase {

  /**
   * The asset release route context service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextProviderInterface
   */
  protected $assetReleaseContext;

  /**
   * Constructs a new event subscriber instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Plugin\Context\ContextProviderInterface $asset_release_context
   *   The asset release route context service.
   */
  public function __construct(EntityTypeBundleInfoInterface $bundle_info, ContextProviderInterface $asset_release_context) {
    parent::__construct($bundle_info);
    $this->assetReleaseContext = $asset_release_context;
  }

  /**
   * {@inheritdoc}
   */
  public function addLinks(AddGroupContentEvent $event): void {
    // Add distribution under a release.
    $release_contexts = $this->assetReleaseContext->getRuntimeContexts(['asset_release']);
    if ($release_contexts && $release_contexts['asset_release']->hasContextValue()) {
      $route_parameters = [
        'rdf_entity' => $release_contexts['asset_release']->getContextValue()->id(),
      ];
      $label = $this->bundleInfo->getBundleInfo('rdf_entity')['asset_distribution']['label_singular'];
      $this->addOneLink($label, $route_parameters, $event);
      // Exit here. We are also in the solution context and we don't want to
      // show this link twice.
      return;
    }

    // Add distribution under a solution.
    if ($event->getGroup() instanceof SolutionInterface) {
      parent::addLinks($event);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getBundles(): array {
    return [
      'rdf_entity' => [
        'asset_distribution',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getRouteName(): string {
    return 'asset_distribution.asset_release_asset_distribution.add';
  }

  /**
   * {@inheritdoc}
   */
  protected function getRouteParameters(AddGroupContentEvent $event, string $entity_type_id, string $bundle_id): array {
    return [
      'rdf_entity' => $event->getGroup()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected static function getPriority(): int {
    return 70;
  }

}
