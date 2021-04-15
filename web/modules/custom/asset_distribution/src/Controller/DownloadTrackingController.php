<?php

declare(strict_types = 1);

namespace Drupal\asset_distribution\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\asset_distribution\Form\AnonymousDownloadForm;
use Drupal\file\FileInterface;
use Drupal\file\FileUsage\FileUsageInterface;
use Drupal\file_url\FileUrlHandler;
use Drupal\sparql_entity_storage\SparqlEntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller to handle the tracking of distribution downloads.
 */
class DownloadTrackingController extends ControllerBase {

  /**
   * The download_event entity storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $eventStorage;

  /**
   * The file URL handler service.
   *
   * @var \Drupal\file_url\FileUrlHandler
   */
  protected $fileUrlHandler;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The RDF entity storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $sparqlStorage;

  /**
   * The file usage service.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected $fileUsage;

  /**
   * Instantiates a new DownloadTrackingController object.
   *
   * @param \Drupal\sparql_entity_storage\SparqlEntityStorageInterface $sparql_storage
   *   The RDF entity storage.
   * @param \Drupal\Core\Entity\ContentEntityStorageInterface $event_storage
   *   The download event entity storage.
   * @param \Drupal\file_url\FileUrlHandler $file_url_handler
   *   The file URL handler service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current logged in user.
   * @param \Drupal\file\FileUsage\FileUsageInterface $file_usage
   *   The file usage service.
   */
  public function __construct(SparqlEntityStorageInterface $sparql_storage, ContentEntityStorageInterface $event_storage, FileUrlHandler $file_url_handler, FormBuilderInterface $form_builder, AccountInterface $current_user, FileUsageInterface $file_usage) {
    $this->sparqlStorage = $sparql_storage;
    $this->eventStorage = $event_storage;
    $this->fileUrlHandler = $file_url_handler;
    $this->formBuilder = $form_builder;
    $this->currentUser = $current_user;
    $this->fileUsage = $file_usage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('rdf_entity'),
      $container->get('entity_type.manager')->getStorage('download_event'),
      $container->get('file_url.handler'),
      $container->get('form_builder'),
      $container->get('current_user'),
      $container->get('file.usage')
    );
  }

  /**
   * Tracks a distribution file download done by a user.
   *
   * @param \Drupal\file\FileInterface $file
   *   The distribution file that has been downloaded.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The generated response.
   */
  public function trackDownload(FileInterface $file) {
    $response = $this->currentUser->isAnonymous()
      ? $this->trackAnonymousDownload($file)
      : $this->trackAuthenticatedDownload($file);

    return $response;
  }

  /**
   * Tracks a download done by an anonymous user.
   *
   * @param \Drupal\file\FileInterface $file
   *   The distribution file that has been downloaded.
   *
   * @return array
   *   The form array.
   */
  protected function trackAnonymousDownload(FileInterface $file) {
    return $this->formBuilder->getForm(AnonymousDownloadForm::class, $file);
  }

  /**
   * Tracks a download done by an registered user user.
   *
   * @param \Drupal\file\FileInterface $file
   *   The distribution file that has been downloaded.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The generated response.
   */
  protected function trackAuthenticatedDownload(FileInterface $file) {
    $usages = $this->fileUsage->listUsage($file);

    // Normally, only one distribution is allowed to use a file and only
    // distributions call this code.
    if (empty($usages['file']['rdf_entity'])) {
      throw new \RuntimeException('No distributions were found using the file with ID ' . $file->id());
    }
    if (count($usages['file']['rdf_entity']) > 1) {
      throw new \RuntimeException('More than one distributions were found for the file with ID ' . $file->id());
    }
    $distribution = $this->sparqlStorage->load(key($usages['file']['rdf_entity']));

    /** @var \Drupal\solution\Entity\SolutionInterface|\Drupal\asset_release\Entity\AssetReleaseInterface $parent */
    $parent = $distribution->getParent();

    $event = $this->eventStorage->create([
      'uid' => $this->currentUser->id(),
      'file' => $file->id(),
      'parent_entity_type' => $parent->getEntityTypeId(),
      'parent_entity_id' => $parent->id(),
    ]);
    $event->save();

    $response = new AjaxResponse();
    $response->setStatusCode(Response::HTTP_NO_CONTENT);

    return $response;
  }

  /**
   * Access callback that checks that the file is a valid distribution one.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file being downloaded.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user whom to check access for.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Allowed when the file exists, neutral otherwise.
   */
  public function isDistributionFile(FileInterface $file, AccountInterface $account) {
    $query = $this->sparqlStorage->getQuery();
    $file_url_handler = $this->fileUrlHandler;

    // Verify that the file exists and it's attached to a solution.
    $query->condition($this->sparqlStorage->getEntityType()->getKey('bundle'), 'asset_distribution')
      ->condition('field_ad_access_url', $file_url_handler::fileToUrl($file));
    $results = $query->execute();

    if (empty($results)) {
      return AccessResult::forbidden();
    }

    $entity = $this->sparqlStorage->load(array_pop($results));

    return $entity->access('view', $account, TRUE);
  }

}
