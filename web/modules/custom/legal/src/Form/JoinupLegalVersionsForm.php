<?php

declare(strict_types = 1);

namespace Drupal\joinup_legal\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\entity_legal\Entity\EntityLegalDocument;
use Drupal\entity_legal\Entity\EntityLegalDocumentVersion;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Shows a list of versions of 'legal_notice' legal document.
 */
class JoinupLegalVersionsForm extends FormBase {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Builds a new form instance.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $header = [
      'title' => $this->t('Title'),
      'version' => $this->t('Version'),
      'created' => $this->t('Created'),
      'changed' => $this->t('Updated'),
      'operations' => $this->t('Operations'),
    ];

    /** @var \Drupal\entity_legal\EntityLegalDocumentInterface $document */
    $document = EntityLegalDocument::load('legal_notice');
    $form_state->set('legal_document', $document);

    $destination = ['query' => $this->getRedirectDestination()->getAsArray()];
    $options = [];
    $published_version = NULL;
    /** @var \Drupal\entity_legal\EntityLegalDocumentVersionInterface $version */
    foreach ($document->getAllVersions() as $version) {
      if ($version->isPublished()) {
        $published_version = $version->id();
      }

      $operations = [
        '#type' => 'operations',
        '#links' => [
          'edit' => [
            'title' => $this->t('Edit'),
            'url' => Url::fromRoute('joinup_legal.version.edit', [
              'entity_legal_document' => $document->id(),
              'entity_legal_document_version' => $version->id(),
            ])->mergeOptions($destination),
          ],
        ],
      ];
      $options[$version->id()] = [
        'title' => $version->label(),
        'version' => $version->get('version')->value,
        'created' => $version->getFormattedDate('created'),
        'changed' => $version->getFormattedDate('changed'),
        'operations' => $this->renderer->render($operations),
      ];
    }

    $form['version'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => $this->t('Create a document version to set up a default'),
      '#multiple' => FALSE,
      '#default_value' => $published_version,
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Set published version'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\entity_legal\EntityLegalDocumentInterface $document */
    $document = $form_state->get('legal_document');
    if ($document->getPublishedVersion()->id() != $form_state->getValue('version')) {
      $version = EntityLegalDocumentVersion::load($form_state->getValue('version'));
      $document->setPublishedVersion($version);
      $this->messenger()->addStatus($this->t('%label %version has been published.', [
        '%label' => $version->label(),
        '%version' => $version->get('version')->value,
      ]));
      return;
    }
    $this->messenger()->addStatus($this->t('No changes have been made.'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'joinup_legal_version_collection';
  }

}
