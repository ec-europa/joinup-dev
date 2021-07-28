<?php

declare(strict_types = 1);

namespace Drupal\collection\Controller;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\collection\Entity\CommunityInterface;
use Drupal\meta_entity\MetaEntityRepositoryInterface;
use Drupal\og\OgAccessInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller class for community settings.
 */
class CommunitySettingsController extends ControllerBase {

  /**
   * The meta entity repository service.
   *
   * @var \Drupal\meta_entity\MetaEntityRepositoryInterface
   */
  protected $metaEntityRepository;

  /**
   * The OG access service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * Constructs a new controller instance.
   *
   * @param \Drupal\meta_entity\MetaEntityRepositoryInterface $meta_entity_repository
   *   The meta entity repository service.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access service.
   */
  public function __construct(MetaEntityRepositoryInterface $meta_entity_repository, OgAccessInterface $og_access) {
    $this->metaEntityRepository = $meta_entity_repository;
    $this->ogAccess = $og_access;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('meta_entity.repository'),
      $container->get('og.access')
    );
  }

  /**
   * Provides a controller for the 'collection.settings_form' route title.
   *
   * @param \Drupal\collection\Entity\CommunityInterface $rdf_entity
   *   The collection.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The content as a render array.
   */
  public function title(CommunityInterface $rdf_entity): MarkupInterface {
    return $this->t('@collection community settings', [
      '@collection' => $rdf_entity->label(),
    ]);
  }

  /**
   * Provides a controller for the 'collection.settings_form' route.
   *
   * @param \Drupal\collection\Entity\CommunityInterface $rdf_entity
   *   The collection.
   *
   * @return array
   *   The content as a render array.
   */
  public function settings(CommunityInterface $rdf_entity): array {
    $meta_entity = $this->metaEntityRepository->getMetaEntityForEntity($rdf_entity, 'collection_settings');
    $form_state_additions = [
      'redirect' => Url::fromRoute('entity.rdf_entity.canonical', [
        'rdf_entity' => $rdf_entity->id(),
      ]),
    ];
    return $this->entityFormBuilder()->getForm($meta_entity, 'default', $form_state_additions);
  }

  /**
   * Povides an access controller for the 'collection.settings_form' route.
   *
   * @param \Drupal\collection\Entity\CommunityInterface $rdf_entity
   *   The collection.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(CommunityInterface $rdf_entity): AccessResultInterface {
    $meta_entity = $this->metaEntityRepository->getMetaEntityForEntity($rdf_entity, 'collection_settings');
    $access_result = $meta_entity->access('update', NULL, TRUE);
    if ($access_result->isAllowed()) {
      return $access_result;
    }
    return $access_result->orIf($this->ogAccess->userAccess($rdf_entity, 'update collection_settings meta-entity'));
  }

}
