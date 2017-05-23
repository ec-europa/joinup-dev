<?php

namespace Drupal\asset_distribution\Controller;

use Drupal\asset_distribution\Form\AnonymousDownloadForm;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\FileInterface;
use Drupal\file_url\FileUrlHandler;
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
  protected $rdfStorage;

  /**
   * Instantiates a new DownloadTrackingController object.
   *
   * @param \Drupal\Core\Entity\ContentEntityStorageInterface $rdf_storage
   *   The RDF entity storage.
   * @param \Drupal\Core\Entity\ContentEntityStorageInterface $event_storage
   *   The download event entity storage.
   * @param \Drupal\file_url\FileUrlHandler $file_url_handler
   *   The file URL handler service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current logged in user.
   */
  public function __construct(ContentEntityStorageInterface $rdf_storage, ContentEntityStorageInterface $event_storage, FileUrlHandler $file_url_handler, FormBuilderInterface $form_builder, AccountInterface $current_user) {
    $this->rdfStorage = $rdf_storage;
    $this->eventStorage = $event_storage;
    $this->fileUrlHandler = $file_url_handler;
    $this->formBuilder = $form_builder;
    $this->currentUser = $current_user;
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
      $container->get('current_user')
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
    $event = $this->eventStorage->create([
      'uid' => $this->currentUser->id(),
      'file' => $file->id(),
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
    $query = $this->rdfStorage->getQuery();
    $file_url_handler = $this->fileUrlHandler;

    // Verify that the file exists and it's attached to a solution.
    $query->condition($this->rdfStorage->getEntityType()->getKey('bundle'), 'asset_distribution')
      ->condition('field_ad_access_url', $file_url_handler::fileToUrl($file));
    $results = $query->execute();

    if (empty($results)) {
      return AccessResult::forbidden();
    }

    $entity = $this->rdfStorage->load(array_pop($results));

    return $entity->access('view', $account, TRUE);
  }

}
