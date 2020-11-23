<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\joinup_licence\Entity\LicenceInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for licence compatibility reports.
 */
class LicenceCompatibilityController extends ControllerBase {

  /**
   * Renders a page that contains a licence compatibility check.
   *
   * @param \Drupal\joinup_licence\Entity\LicenceInterface $inbound_licence
   *   The licence used for code or data that is intended to be redistributed as
   *   part of another project.
   * @param \Drupal\joinup_licence\Entity\LicenceInterface $outbound_licence
   *   The licence under which the code or data is intended to be redistributed.
   *
   * @return array
   *   A render array.
   */
  public function check(LicenceInterface $inbound_licence, LicenceInterface $outbound_licence): array {
    try {
      $compatibility_document = $inbound_licence->getCompatibilityDocument($outbound_licence);
    }
    catch (\Exception $e) {
      throw new NotFoundHttpException();
    }

    $view_builder = $this->entityTypeManager()->getViewBuilder($compatibility_document->getEntityTypeId());

    $build = [
      $view_builder->view($compatibility_document),
    ];

    $cache_metadata = new CacheableMetadata();
    $cache_metadata
      ->addCacheableDependency($compatibility_document)
      ->addCacheableDependency($inbound_licence)
      ->addCacheableDependency($outbound_licence)
      ->applyTo($build);

    return $build;
  }

  /**
   * Title callback.
   *
   * @param \Drupal\joinup_licence\Entity\LicenceInterface $inbound_licence
   *   The licence used for code or data that is intended to be redistributed as
   *   part of another project.
   * @param \Drupal\joinup_licence\Entity\LicenceInterface $outbound_licence
   *   The licence under which the code or data is intended to be redistributed.
   *
   * @return array
   *   The title as a render array.
   */
  public function getTitle(LicenceInterface $inbound_licence, LicenceInterface $outbound_licence): array {
    return [
      '#markup' => $this->t('Compatibility between the %inbound (inbound licence) and the %outbound (outbound licence).', [
        '%inbound' => $inbound_licence->label(),
        '%outbound' => $outbound_licence->label(),
      ]),
    ];
  }

}
