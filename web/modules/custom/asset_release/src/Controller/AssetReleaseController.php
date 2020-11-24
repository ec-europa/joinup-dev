<?php

declare(strict_types = 1);

namespace Drupal\asset_release\Controller;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\OgAccessInterface;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for asset release forms.
 *
 * Handles the form to perform actions when it is called by a route that
 * includes an rdf_entity id.
 */
class AssetReleaseController extends ControllerBase {

  /**
   * The OG access handler.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * Constructs a AssetReleaseController.
   *
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access handler.
   */
  public function __construct(OgAccessInterface $og_access) {
    $this->ogAccess = $og_access;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('og.access')
    );
  }

  /**
   * Controller for the base form.
   *
   * We need to override the functionality of the create form for pages
   * that include the rdf_entity id in the url so that the og audience field
   * is auto completed.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The collection rdf_entity.
   *
   * @return array
   *   Return the form array to be rendered.
   */
  public function add(RdfInterface $rdf_entity): array {
    return $this->entityFormBuilder()->getForm($this->createNewAssetRelease($rdf_entity));
  }

  /**
   * Handles access to the asset_release add form through solution pages.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The RDF entity for which the custom page is created.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The RDF entity for which the custom page is created.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result object.
   */
  public function createAssetReleaseAccess(RdfInterface $rdf_entity, ?AccountInterface $account = NULL): AccessResultInterface {
    if ($rdf_entity->bundle() !== 'solution') {
      throw new NotFoundHttpException();
    }

    return $this->ogAccess->userAccessEntityOperation('create', $this->createNewAssetRelease($rdf_entity), $account);
  }

  /**
   * Returns a build array for the solution releases overview page.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The solution rdf entity.
   *
   * @return array|\Drupal\Core\Routing\TrustedRedirectResponse
   *   The build array for the page or a 404 response.
   */
  public function overview(RdfInterface $rdf_entity) {
    /** @var \Drupal\solution\SolutionReleasesAndDistributionsFieldItemList $field */
    $field = $rdf_entity->get('releases_and_distributions');
    if ($field->isEmpty()) {
      return (new TrustedRedirectResponse('/not-found'))
        ->setStatusCode(404)
        ->addCacheableDependency($rdf_entity);
    }
    $entities = $this->sortEntitiesByCreationDate($field->referencedEntities());

    // Put a flag on the standalone distributions so they can be identified for
    // theming purposes.
    foreach ($entities as $entity) {
      if ($entity->bundle() === 'asset_distribution') {
        $entity->standalone = TRUE;
      }
    }

    // Mark the first release as the latest.
    // @see asset_release_preprocess_rdf_entity()
    foreach ($entities as $entity) {
      if ($entity->bundle() === 'asset_release') {
        $entity->is_latest_release = TRUE;
        break;
      }
    }

    $build = [
      [
        '#theme' => 'asset_release_releases_download',
        '#releases' => $entities,
      ],
    ];

    (new CacheableMetadata())
      ->addCacheableDependency($rdf_entity)
      ->applyTo($build);

    return $build;
  }

  /**
   * Page title callback for the solution releases overview.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The solution rdf entity.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The page title.
   */
  public function overviewPageTitle(RdfInterface $rdf_entity): MarkupInterface {
    return $this->t('Releases for %solution solution', ['%solution' => $rdf_entity->label()]);
  }

  /**
   * Access callback for the solution releases overview.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The solution rdf entity.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object to be checked.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account being checked.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown when the rdf entity is not a solution.
   */
  public function overviewAccess(RdfInterface $rdf_entity, RouteMatchInterface $route_match, AccountInterface $account): AccessResultInterface {
    if ($rdf_entity->bundle() !== 'solution') {
      throw new NotFoundHttpException();
    }

    return $rdf_entity->access('view', $account, TRUE);
  }

  /**
   * Creates a new asset_release entity.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The solution that the asset_release is version of.
   *
   * @return \Drupal\rdf_entity\RdfInterface
   *   The unsaved asset_release entity.
   */
  protected function createNewAssetRelease(RdfInterface $rdf_entity): RdfInterface {
    /** @var \Drupal\rdf_entity\RdfInterface $release */
    $release = $this->entityTypeManager()->getStorage('rdf_entity')->create([
      'rid' => 'asset_release',
      'field_isr_is_version_of' => $rdf_entity->id(),
    ]);
    return $release;
  }

  /**
   * Sorts a list of releases and distributions by date.
   *
   * @param \Drupal\rdf_entity\RdfInterface[] $entities
   *   The RDF entities to sort.
   *
   * @return \Drupal\rdf_entity\RdfInterface[]
   *   The sorted RDF entities.
   */
  protected function sortEntitiesByCreationDate(array $entities): array {
    usort($entities, function (RdfInterface $entity1, RdfInterface $entity2): int {
      // Sort entries without a creation date on the bottom so they don't stick
      // to the top for all eternity.
      $ct1 = $entity1->getCreatedTime() ?: 0;
      $ct2 = $entity2->getCreatedTime() ?: 0;
      if ($ct1 == $ct2) {
        return 0;
      }
      return ($ct1 < $ct2) ? 1 : -1;
    });

    return $entities;
  }

}
