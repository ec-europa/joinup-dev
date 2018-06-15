<?php

namespace Drupal\asset_release\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\OgAccessInterface;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class AssetReleaseController.
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
   * The entity query factory service.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $queryFactory;

  /**
   * Constructs a AssetReleaseController.
   *
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access handler.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query factory service.
   */
  public function __construct(OgAccessInterface $og_access, QueryFactory $query_factory) {
    $this->ogAccess = $og_access;
    $this->queryFactory = $query_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('og.access'),
      $container->get('entity.query')
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
  public function add(RdfInterface $rdf_entity) {
    return $this->entityFormBuilder()->getForm($this->createNewAssetRelease($rdf_entity));
  }

  /**
   * Handles access to the asset_release add form through solution pages.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The RDF entity for which the custom page is created.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The RDF entity for which the custom page is created.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result object.
   */
  public function createAssetReleaseAccess(RdfInterface $rdf_entity, AccountInterface $account = NULL) {
    if ($rdf_entity->bundle() !== 'solution') {
      throw new NotFoundHttpException();
    }

    return $this->ogAccess->userAccessEntity('create', $this->createNewAssetRelease($rdf_entity), $account);
  }

  /**
   * Returns a build array for the solution releases overview page.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The solution rdf entity.
   *
   * @return array
   *   The build array for the page.
   */
  public function overview(RdfInterface $rdf_entity) {
    // Retrieve all releases for this solution.
    $ids = $this->queryFactory->get('rdf_entity')
      ->condition('rid', 'asset_release')
      ->condition('field_isr_is_version_of', $rdf_entity->id())
      // @todo: This is a temporary fix. We need to implement the sort in the
      // rdf entity module in order to be able to handle paging.
      // @see: https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-2788
      // ->sort('created', 'DESC')
      ->execute();

    /** @var \Drupal\rdf_entity\Entity\Rdf[] $releases */
    $releases = Rdf::loadMultiple($ids);

    // Filter out any release that the current user cannot access.
    // @todo Filter out any unpublished release. See ISAICP-3393.
    $releases = array_filter($releases, function ($release) {
      return $release->access('view');
    });
    $standalone_distributions = $rdf_entity->get('field_is_distribution')->referencedEntities();

    // Put a flag on the standalone distributions so they can be identified for
    // theming purposes.
    foreach ($standalone_distributions as $standalone_distribution) {
      $standalone_distribution->standalone = TRUE;
    }

    $entities = $this->sortEntitiesByCreationDate(array_merge($releases, $standalone_distributions));

    // Mark the first release as the latest.
    // @see asset_release_preprocess_rdf_entity()
    foreach ($entities as $entity) {
      if ($entity->bundle() === 'asset_release') {
        $entity->is_latest_release = TRUE;
        break;
      }
    }

    return [
      '#theme' => 'asset_release_releases_download',
      '#releases' => $entities,
    ];
  }

  /**
   * Page title callback for the solution releases overview.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The solution rdf entity.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page title.
   */
  public function overviewPageTitle(RdfInterface $rdf_entity) {
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
  public function overviewAccess(RdfInterface $rdf_entity, RouteMatchInterface $route_match, AccountInterface $account) {
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
   * @return \Drupal\Core\Entity\EntityInterface
   *   The unsaved asset_release entity.
   */
  protected function createNewAssetRelease(RdfInterface $rdf_entity) {
    return $this->entityTypeManager()->getStorage('rdf_entity')->create([
      'rid' => 'asset_release',
      'field_isr_is_version_of' => $rdf_entity->id(),
    ]);
  }

  /**
   * Sorts a list of releases and distributions by date.
   *
   * @param \Drupal\rdf_entity\Entity\Rdf[] $entities
   *   The RDF entities to sort.
   *
   * @return \Drupal\rdf_entity\Entity\Rdf[]
   *   The sorted RDF entities.
   */
  protected function sortEntitiesByCreationDate(array $entities) {
    usort($entities, function ($entity1, $entity2) {
      // Sort entries without a creation date on the bottom so they don't
      // stick to the top for all eternity.
      /** @var \Drupal\rdf_entity\Entity\Rdf $entity1 */
      $ct1 = $entity1->getCreatedTime() ?: 0;
      /** @var \Drupal\rdf_entity\Entity\Rdf $entity2 */
      $ct2 = $entity2->getCreatedTime() ?: 0;
      if ($ct1 == $ct2) {
        return 0;
      }
      return ($ct1 < $ct2) ? 1 : -1;
    });

    return $entities;
  }

}
