<?php

declare(strict_types = 1);

namespace Drupal\collection\Entity;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\joinup_bundle_class\JoinupBundleClassFieldAccessTrait;
use Drupal\joinup_bundle_class\JoinupBundleClassMetaEntityTrait;
use Drupal\joinup_bundle_class\LogoTrait;
use Drupal\joinup_bundle_class\ShortIdTrait;
use Drupal\joinup_featured\FeaturedContentTrait;
use Drupal\joinup_group\Entity\GroupTrait;
use Drupal\joinup_publication_date\Entity\EntityPublicationTimeFallbackTrait;
use Drupal\joinup_workflow\ArchivableEntityTrait;
use Drupal\joinup_workflow\EntityWorkflowStateTrait;
use Drupal\og\OgMembershipInterface;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\topic\Entity\TopicReferencingEntityTrait;

/**
 * Entity subclass for the 'collection' bundle.
 */
class Collection extends Rdf implements CollectionInterface {

  use ArchivableEntityTrait;
  use EntityPublicationTimeFallbackTrait;
  use EntityWorkflowStateTrait;
  use FeaturedContentTrait;
  use GroupTrait {
    createMembership as protected createMembershipTrait;
  }
  use JoinupBundleClassFieldAccessTrait;
  use JoinupBundleClassMetaEntityTrait;
  use LogoTrait;
  use ShortIdTrait;
  use StringTranslationTrait;
  use TopicReferencingEntityTrait;

  /**
   * {@inheritdoc}
   *
   * phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found
   */
  public static function create(array $values = []): CollectionInterface {
    // Delegate to the parent method. This is only overridden to provide the
    // correct return type.
    return parent::create($values);
  }

  /**
   * {@inheritdoc}
   */
  public function getSolutions(bool $only_published = FALSE): array {
    $ids = $this->getSolutionIds($only_published);
    if (empty($ids)) {
      return $ids;
    }
    return $this
      ->entityTypeManager()
      ->getStorage('rdf_entity')
      ->loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getSolutionIds(bool $only_published = FALSE): array {
    $ids = $this->getReferencedEntityIds('field_ar_affiliates')['rdf_entity'] ?? [];

    if ($ids && $only_published) {
      // Published solutions are stored in the 'default' graph.
      $ids = $this->entityTypeManager()
        ->getStorage('rdf_entity')
        ->getQuery()
        ->graphs(['default'])
        ->condition('rid', 'solution')
        ->condition('id', $ids, 'IN')
        ->execute();
      $ids = array_values($ids);
    }

    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkflowStateFieldName(): string {
    return 'field_ar_state';
  }

  /**
   * {@inheritdoc}
   */
  public function getLogoFieldName(): string {
    return 'field_ar_logo';
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupModerationFieldName(): string {
    return 'field_ar_moderation';
  }

  /**
   * {@inheritdoc}
   */
  public function getContentCreationFieldName(): string {
    return 'field_ar_content_creation';
  }

  /**
   * {@inheritdoc}
   */
  public function getGlossarySettings(): array {
    /** @var \Drupal\meta_entity\Entity\MetaEntityInterface $meta_entity */
    $meta_entity = $this->get('settings')->entity;
    if (!$meta_entity) {
      throw new \Exception("The {$this->label()} collection doesn't have an associated 'collection_settings' meta-entity.");
    }
    return [
      'link_only_first' => (bool) $meta_entity->get('glossary_link_only_first')->value,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function createMembership(?int $uid = NULL, ?string $role = 'member', ?string $state = NULL): OgMembershipInterface {
    // If the membership state is not defined, default to 'active' for open
    // collections, and 'pending' for closed collections.
    if (empty($state)) {
      $state = $this->isClosed() ? OgMembershipInterface::STATE_PENDING : OgMembershipInterface::STATE_ACTIVE;
    }

    return $this->createMembershipTrait($uid, $role, $state);
  }

  /**
   * {@inheritdoc}
   */
  public function isClosed(): bool {
    return (bool) $this->getMainPropertyValue('field_ar_closed');
  }

  /**
   * {@inheritdoc}
   */
  public function getNewMembershipSuccessMessage(OgMembershipInterface $membership): TranslatableMarkup {
    $parameters = [
      '%group' => $this->getName(),
    ];
    return $membership->getState() === OgMembershipInterface::STATE_ACTIVE ?
      $this->t('You are now a member of %group.', $parameters) :
      $this->t('Your membership to the %group collection is under approval.', $parameters);
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
        return $this->t('You cannot join %group because your account has been blocked.', $parameters);

      case OgMembershipInterface::STATE_PENDING:
        return $this->t('You have already joined the %group collection but your membership still needs to be approved by a facilitator.', $parameters);

      default:
        return $this->t('You already are a member of %group.', $parameters);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doGetGroupContentIds(): array {
    $ids = ['node' => $this->getNodeGroupContent()];
    $solutions = $this->getSolutions();
    $ids = NestedArray::mergeDeep($ids, [
      'rdf_entity' => [
        'solution' => array_keys($solutions),
      ],
    ]);
    foreach ($solutions as $solution) {
      $ids = NestedArray::mergeDeep($ids, $solution->getGroupContentIds());
    }
    return $ids;
  }

}
