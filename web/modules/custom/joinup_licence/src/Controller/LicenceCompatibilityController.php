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
   * @param \Drupal\joinup_licence\Entity\LicenceInterface $use_licence
   *   The licence used for code or data that is intended to be redistributed as
   *   part of another project.
   * @param \Drupal\joinup_licence\Entity\LicenceInterface $redistribute_as_licence
   *   The licence under which the code or data is intended to be redistributed.
   *
   * @return array
   *   A render array.
   */
  public function check(LicenceInterface $use_licence, LicenceInterface $redistribute_as_licence): array {
    try {
      $compatibility_document = $use_licence->getCompatibilityDocument($redistribute_as_licence);
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
      ->addCacheableDependency($use_licence)
      ->addCacheableDependency($redistribute_as_licence)
      ->applyTo($build);

    return $build;
  }

  /**
   * Title callback.
   *
   * @param \Drupal\joinup_licence\Entity\LicenceInterface $use_licence
   *   The licence used for code or data that is intended to be redistributed as
   *   part of another project.
   * @param \Drupal\joinup_licence\Entity\LicenceInterface $redistribute_as_licence
   *   The licence under which the code or data is intended to be redistributed.
   *
   * @return array
   *   The title as a render array.
   */
  public function getTitle(LicenceInterface $use_licence, LicenceInterface $redistribute_as_licence): array {
    return [
      '#markup' => $this->t('Can %use_licence be redistributed as %redistribute_as_licence?', [
        '%use_licence' => $use_licence->label(),
        '%redistribute_as_licence' => $redistribute_as_licence->label(),
      ]),
    ];
  }

}
