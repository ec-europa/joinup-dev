<?php

declare(strict_types = 1);

namespace Drupal\asset_release\Entity;

use Drupal\joinup_bundle_class\JoinupBundleClassFieldAccessTrait;
use Drupal\joinup_bundle_class\LogoTrait;
use Drupal\joinup_group\Entity\GroupInterface;
use Drupal\joinup_group\Exception\MissingGroupException;
use Drupal\joinup_publication_date\Entity\EntityPublicationTimeFallbackTrait;
use Drupal\joinup_workflow\EntityWorkflowStateTrait;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\solution\Entity\SolutionContentTrait;

/**
 * Bundle class for the 'asset_release' bundle.
 */
class AssetRelease extends Rdf implements AssetReleaseInterface {

  use EntityPublicationTimeFallbackTrait;
  use EntityWorkflowStateTrait;
  use JoinupBundleClassFieldAccessTrait;
  use LogoTrait;
  use SolutionContentTrait;

  /**
   * {@inheritdoc}
   */
  public function getGroup(): GroupInterface {
    $group = $this->getFirstReferencedEntity('field_isr_is_version_of');
    if (empty($group) || !$group instanceof GroupInterface) {
      throw new MissingGroupException();
    }

    return $group;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupId(): string {
    $ids = $this->getReferencedEntityIds('field_isr_is_version_of');
    if (empty($ids['rdf_entity'])) {
      throw new MissingGroupException();
    }
    return array_shift($ids['rdf_entity']);
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkflowStateFieldName(): string {
    return 'field_isr_state';
  }

  /**
   * {@inheritdoc}
   */
  public function getLogoFieldName(): string {
    return 'field_isr_logo';
  }

  /**
   * {@inheritdoc}
   */
  public function isLatestRelease(): bool {
    return $this->id() === $this->getSolution()->getLatestReleaseId();
  }

  /**
   * {@inheritdoc}
   */
  public function getVersion(): ?string {
    /** @var \Drupal\Core\Field\FieldItemListInterface $field */
    $field = $this->get('field_isr_release_number');
    if ($field->isEmpty() || !($field->first()->value)) {
      // @todo Replace the deprecation error with an exception in ISAICP-6217.
      // @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-6217
      @trigger_error('A release without version number is deprecated. The version number is required in ISAICP-6217.', E_USER_DEPRECATED);
      return '';
    }
    return $field->first()->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getDistributionIds(): array {
    return $this->getReferencedEntityIds('field_isr_distribution')['rdf_entity'] ?? [];
  }

}
