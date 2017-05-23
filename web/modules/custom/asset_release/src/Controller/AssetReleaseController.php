<?php

namespace Drupal\asset_release\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\OgAccessInterface;
use Drupal\og\OgGroupAudienceHelperInterface;
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

  protected $fieldsToCopy = [
    'field_is_description' => 'field_isr_description',
    'field_is_solution_type' => 'field_isr_solution_type',
    'field_is_contact_information' => 'field_isr_contact_information',
    'field_is_owner' => 'field_isr_owner',
    'field_is_related_solutions' => 'field_isr_related_solutions',
    'field_is_included_asset' => 'field_isr_included_asset',
    'field_is_translation' => 'field_isr_translation',
    'field_policy_domain' => 'field_policy_domain',
  ];

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
    // Setup the values for the release.
    $values = [
      'rid' => 'asset_release',
      'field_isr_is_version_of' => $rdf_entity->id(),
    ];

    foreach ($this->fieldsToCopy as $solution_field => $release_field) {
      if (!empty($rdf_entity->get($solution_field)->getValue())) {
        $values[$release_field] = $rdf_entity->get($solution_field)->getValue();
      }
    }

    $asset_release = $this->entityTypeManager()
      ->getStorage('rdf_entity')
      ->create($values);

    $form = $this->entityFormBuilder()->getForm($asset_release);

    return $form;
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
    $view_builder = $this->entityTypeManager()->getViewBuilder('rdf_entity');

    // Retrieve all releases for this solution.
    $ids = $this->queryFactory->get('rdf_entity')
      ->condition('rid', 'asset_release')
      ->condition('field_isr_is_version_of', $rdf_entity->id())
      // @todo: This is a temporary fix. We need to implement the sort in the
      // rdf entity module in order to be able to handle paging.
      // @see: https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-2788
      // ->sort('field_isr_creation_date', 'DESC')
      ->execute();

    /** @var \Drupal\rdf_entity\Entity\Rdf[] $releases */
    $releases = Rdf::loadMultiple($ids);

    // Filter out any release that the current user cannot access.
    // @todo Filter out any unpublished release. See ISAICP-3393.
    $releases = array_filter($releases, function ($release) {
      return $release->access('view');
    });

    // Retrieve all standalone distributions for this solution. These are
    // downloads that are not associated with a release.
    // The standalone distributions are not flagged in any way, and the relation
    // between releases and distributions is backwards (the relation is present
    // on the parent entity). We work around this by retrieving all
    // distributions excluding the ones that are associated with a release.
    $release_distribution_ids = [];
    foreach ($releases as $release) {
      foreach ($release->get('field_isr_distribution')->getValue() as $distribution_reference) {
        $release_distribution_ids[] = $distribution_reference['target_id'];
      }
    }

    $query = $this->queryFactory->get('rdf_entity');
    $query
      ->condition('rid', 'asset_distribution')
      ->condition(OgGroupAudienceHelperInterface::DEFAULT_FIELD, $rdf_entity->id());
    if (!empty($release_distribution_ids)) {
      $query->condition('id', $release_distribution_ids, 'NOT IN');
    }
    $standalone_distribution_ids = $query->execute();
    $standalone_distributions = Rdf::loadMultiple($standalone_distribution_ids);

    // Put a flag on the standalone distributions so they can be identified for
    // theming purposes.
    foreach ($standalone_distributions as $standalone_distribution) {
      $standalone_distribution->standalone = TRUE;
    }

    $releases = array_merge($releases, $standalone_distributions);
    usort($releases, function ($release1, $release2) {
      $get_creation_date = function (RdfInterface $entity) {
        $date = $entity->bundle() === 'asset_release' ? $entity->field_isr_creation_date->value : $entity->field_ad_creation_date->value;
        // Sort entries without a creation date on the bottom so they don't
        // stick to the top for all eternity.
        if (empty($date)) {
          $date = '1970-01-01';
        }
        return strtotime($date);
      };

      $ct1 = $get_creation_date($release1);
      $ct2 = $get_creation_date($release2);
      if ($ct1 == $ct2) {
        return 0;
      }
      return ($ct1 < $ct2) ? 1 : -1;
    });

    // Flag the first release so it can be themed accordingly.
    $first_release = reset($releases);
    $first_release->top_of_timeline = TRUE;

    $build_array = [];
    /** @var \Drupal\rdf_entity\RdfInterface $release */
    foreach ($releases as $release) {
      $build_array[] = $view_builder->view($release, 'compact');
    }

    return [
      '#theme' => 'asset_release_releases_download',
      '#releases' => $build_array,
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

}
