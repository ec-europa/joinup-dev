<?php

declare(strict_types = 1);

namespace Drupal\solution\Entity;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\asset_release\Entity\AssetReleaseInterface;
use Drupal\collection\Entity\CollectionInterface;
use Drupal\collection\Exception\MissingCollectionException;
use Drupal\joinup_bundle_class\JoinupBundleClassFieldAccessTrait;
use Drupal\joinup_bundle_class\JoinupBundleClassMetaEntityTrait;
use Drupal\joinup_bundle_class\LogoTrait;
use Drupal\joinup_bundle_class\ShortIdTrait;
use Drupal\joinup_featured\FeaturedContentTrait;
use Drupal\joinup_group\Entity\GroupInterface;
use Drupal\joinup_group\Entity\GroupTrait;
use Drupal\joinup_group\Entity\PinnableGroupContentTrait;
use Drupal\joinup_group\Exception\MissingGroupException;
use Drupal\joinup_publication_date\Entity\EntityPublicationTimeFallbackTrait;
use Drupal\joinup_workflow\EntityWorkflowStateTrait;
use Drupal\og\OgMembershipInterface;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\topic\Entity\TopicReferencingEntityTrait;

/**
 * Entity subclass for the 'solution' bundle.
 */
class Solution extends Rdf implements SolutionInterface {

  use EntityPublicationTimeFallbackTrait;
  use EntityWorkflowStateTrait;
  use FeaturedContentTrait;
  use GroupTrait;
  use JoinupBundleClassFieldAccessTrait;
  use JoinupBundleClassMetaEntityTrait;
  use LogoTrait;
  use PinnableGroupContentTrait;
  use ShortIdTrait;
  use StringTranslationTrait;
  use TopicReferencingEntityTrait;

  /**
   * {@inheritdoc}
   */
  public function getCollection(): CollectionInterface {
    try {
      /** @var \Drupal\collection\Entity\CollectionInterface $group */
      $group = $this->getGroup();
    }
    catch (MissingGroupException $exception) {
      throw new MissingCollectionException($exception->getMessage(), 0, $exception);
    }
    return $group;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup(): GroupInterface {
    $field_item = $this->getFirstItem('collection');
    if (!$field_item || $field_item->isEmpty()) {
      throw new MissingGroupException();
    }
    $collection = $field_item->entity;
    if (empty($collection)) {
      // The collection entity can be empty in case it has been deleted and the
      // affiliated solutions have not yet been garbage collected.
      throw new MissingGroupException();
    }
    return $collection;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupId(): string {
    $ids = $this->getReferencedEntityIds('collection');
    if (empty($ids['rdf_entity'])) {
      throw new MissingGroupException();
    }
    return array_shift($ids['rdf_entity']);
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkflowStateFieldName(): string {
    return 'field_is_state';
  }

  /**
   * {@inheritdoc}
   */
  public function getReleaseIds(): array {
    return $this->getReferencedEntityIds('field_is_has_version')['rdf_entity'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getReleases(): array {
    return $this->entityTypeManager()->getStorage('rdf_entity')
      ->loadMultiple($this->getReleaseIds());
  }

  /**
   * {@inheritdoc}
   */
  public function getDistributionIds(): array {
    return $this->getReferencedEntityIds('field_is_distribution')['rdf_entity'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLatestReleaseId(): ?string {
    return $this->get('latest_release')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getLatestRelease(): ?AssetReleaseInterface {
    return $this->get('latest_release')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getAffiliatedCollections(): array {
    $collections = [];
    foreach ($this->getReferencedEntities('collection') as $collection) {
      $collections[$collection->id()] = $collection;
    }
    return $collections;
  }

  /**
   * {@inheritdoc}
   */
  public function getAffiliatedCollectionIds(): array {
    return $this->getReferencedEntityIds('collection')['rdf_entity'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getPinnableGroups(): array {
    return $this->getAffiliatedCollections();
  }

  /**
   * {@inheritdoc}
   */
  public function getPinnableGroupIds(): array {
    return $this->getAffiliatedCollectionIds();
  }

  /**
   * {@inheritdoc}
   */
  public function getLogoFieldName(): string {
    return 'field_is_logo';
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupModerationFieldName(): string {
    return 'field_is_moderation';
  }

  /**
   * {@inheritdoc}
   */
  public function getContentCreationFieldName(): string {
    return 'field_is_content_creation';
  }

  /**
   * {@inheritdoc}
   */
  public function doGetGroupContentIds(): array {
    $ids = ['node' => $this->getNodeGroupContent()];
    $releases = $this->getReleases();
    $ids = NestedArray::mergeDeep($ids, [
      'rdf_entity' => [
        'asset_release' => array_keys($releases),
        'asset_distribution' => $this->getDistributionIds(),
      ],
    ]);
    foreach ($releases as $release) {
      $ids = NestedArray::mergeDeep($ids, [
        'rdf_entity' => [
          'asset_distribution' => $release->getDistributionIds(),
        ],
      ]);
    }
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getNewMembershipSuccessMessage(OgMembershipInterface $membership): TranslatableMarkup {
    return $this->t('You have subscribed to this solution and will receive notifications for it. To manage your subscriptions go to <em>My subscriptions</em> in your user menu.');
  }

  /**
   * {@inheritdoc}
   */
  public function getExistingMembershipMessage(OgMembershipInterface $membership): TranslatableMarkup {
    $parameters = [
      '%group' => $this->getName(),
    ];
    switch ($membership->getState()) {
      case OgMembershipInterface::STATE_BLOCKED:
        return $this->t('You cannot subscribe to %group because your account has been blocked.', $parameters);

      default:
        return $this->t('You are already subscribed to %group.', $parameters);
    }
  }

}
