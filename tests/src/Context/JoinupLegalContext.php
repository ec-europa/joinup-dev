<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\entity_legal\Entity\EntityLegalDocumentAcceptance;
use Drupal\entity_legal\Entity\EntityLegalDocumentVersion;
use Drupal\joinup\Traits\EntityTrait;

/**
 * Behat step definitions for the Joinup Legal module.
 */
class JoinupLegalContext extends RawDrupalContext {

  use EntityTrait;

  /**
   * Field aliases.
   *
   * @var string[]
   */
  const ALIASES = [
    'Document' => 'document_name',
    'Label' => 'label',
    'Published' => 'published',
    'Acceptance label' => 'acceptance_label',
    'Content' => 'entity_legal_document_text',
  ];

  /**
   * Testing legal document versions.
   *
   * @var \Drupal\entity_legal\EntityLegalDocumentVersionInterface[]
   */
  protected $legalDocumentVersions = [];

  /**
   * Creates new legal document versions.
   *
   * Table format:
   * @codingStandardsIgnoreStart
   * | Document       | Label | Published | Acceptance label      | Content           |
   * | Legal notice   | v2.3  | yes       | Do you agree?         | Take care!        |
   * | Privacy policy | 1.0   |           | Agree with the policy | All about privacy |
   * @codingStandardsIgnoreEnd
   *
   * Required: Document, Label, Acceptance label.
   *
   * @param \Behat\Gherkin\Node\TableNode $collection_table
   *   The collection data.
   *
   * @throws \Exception
   *   Thrown when a column name is incorrect.
   *
   * @Given (the following )legal document version(s):
   */
  public function givenLegalDocumentVersions(TableNode $collection_table): void {
    $published = [];
    foreach ($collection_table->getColumnsHash() as $version) {
      $values = [];
      // Replace the column aliases with the actual field names.
      foreach ($version as $key => $value) {
        if (array_key_exists($key, static::ALIASES)) {
          $values[static::ALIASES[$key]] = $value;
        }
        else {
          throw new \Exception("Unknown column '$key' in collection table.");
        }
      }

      foreach (['document_name', 'label', 'acceptance_label'] as $required) {
        if (!isset($required)) {
          throw new \Exception("Missing column " . array_flip(static::ALIASES)[$required] . "''.");
        }
      }

      $document = $this->getEntityByLabel(ENTITY_LEGAL_DOCUMENT_ENTITY_NAME, $values['document_name']);
      $values['document_name'] = $document->id();

      $values['published'] = strtolower($values['published']);
      if (!in_array($values['published'], ['yes', 'no']) && !empty($values['published'])) {
        throw new \Exception("Wrong value for 'Published'. Accepted: 'yes', 'no' or empty.");
      }
      $values['published'] = $values['published'] === 'yes';

      if ($values['published'] && isset($published[$values['document_name']])) {
        throw new \Exception("Only one version can be publishes within the same Document.");
      }
      $published[$values['document_name']] = TRUE;

      $values += [
        'entity_legal_document_text' => NULL,
      ];

      $document_version = EntityLegalDocumentVersion::create([
        'document_name' => $values['document_name'],
        'published' => $values['published'],
        'label' => $values['label'],
        'acceptance_label' => $values['acceptance_label'],
        'entity_legal_document_text' => [
          'value' => $values['entity_legal_document_text'],
          'format' => 'content_editor',
        ],
      ]);
      $document_version->save();
      $this->legalDocumentVersions[$document_version->id()] = $document_version;
    }
  }

  /**
   * Publishes a given version of a given legal document.
   *
   * @param string $document_label
   *   The legal document label.
   * @param string $version_label
   *   The version of the legal document to be published.
   *
   * @throws \Exception
   *   When legal document with label $document_label doesn't exist or the legal
   *   document with version $version doesn't exist.
   *
   * @Given (the )version :version_label of :document_label (legal document )is published
   */
  public function publishLegalDocumentVersion(string $document_label, string $version_label): void {
    /** @var \Drupal\entity_legal\EntityLegalDocumentInterface $document */
    $document = $this->getEntityByLabel(ENTITY_LEGAL_DOCUMENT_ENTITY_NAME, $document_label);
    /** @var \Drupal\entity_legal\EntityLegalDocumentVersionInterface $version */
    $version = $this->getEntityByLabel(ENTITY_LEGAL_DOCUMENT_VERSION_ENTITY_NAME, $version_label, $document->id());
    $document->setPublishedVersion($version);
  }

  /**
   * Deletes a given legal document version.
   *
   * @param string $document_label
   *   The legal document label.
   * @param string $version_label
   *   The version of the legal document to be published.
   *
   * @throws \RuntimeException
   *   Thrown when an entity with the given label does not exist.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failure on entity deletion.
   *
   * @Given I delete the version :version_label of( document) :document_label
   */
  public function deleteLegalDocumentVersion(string $document_label, string $version_label): void {
    $document = $this->getEntityByLabel(ENTITY_LEGAL_DOCUMENT_ENTITY_NAME, $document_label);
    $version = $this->getEntityByLabel(ENTITY_LEGAL_DOCUMENT_VERSION_ENTITY_NAME, $version_label, $document->id());
    $version->delete();
  }

  /**
   * Adds a legal document acceptance record.
   *
   * @param string $document_label
   *   The legal document label.
   *
   * @throws \Exception
   *   When the current user is not logged in.
   * @throws \InvalidArgumentException
   *   When the given legal document has no published version.
   *
   * @Given I accept the :document_label agreement
   */
  public function iAcceptTheLegalDocument(string $document_label): void {
    if ($this->getUserManager()->currentUserIsAnonymous()) {
      throw new \Exception('User should be logged in when accepting a document.');
    }

    /** @var \Drupal\entity_legal\EntityLegalDocumentInterface $document */
    $document = $this->getEntityByLabel(ENTITY_LEGAL_DOCUMENT_ENTITY_NAME, $document_label);
    if (!$published_version = $document->getPublishedVersion()) {
      throw new \InvalidArgumentException("Legal document '{$document->label()}' has no published version.");
    }

    EntityLegalDocumentAcceptance::create([
      'document_version_name' => $published_version->id(),
      'uid' => $this->getUserManager()->getCurrentUser()->uid,
    ])->save();
  }

  /**
   * Clears the testing legal document versions.
   *
   * @afterScenario
   */
  public function clearLegalNoticeVersions(): void {
    if ($this->legalDocumentVersions) {
      \Drupal::entityTypeManager()->getStorage('entity_legal_document_version')
        ->delete($this->legalDocumentVersions);
    }
  }

}
