<?php

declare(strict_types = 1);

namespace Drupal\joinup\Controller;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a CSV export of the list of users as a download.
 *
 * This is serving the output from the batch process of ExportUserListForm.
 *
 * @see \Drupal\joinup\Form\ExportUserListForm
 */
class DownloadUserListController extends ControllerBase {

  /**
   * The CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfTokenGenerator;

  /**
   * The filesystem service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a DownloadUserListController.
   *
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrfTokenGenerator
   *   The CSRF token generator service.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The filesystem service.
   */
  public function __construct(CsrfTokenGenerator $csrfTokenGenerator, FileSystemInterface $fileSystem) {
    $this->csrfTokenGenerator = $csrfTokenGenerator;
    $this->fileSystem = $fileSystem;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('csrf_token'),
      $container->get('file_system')
    );
  }

  /**
   * Provides a page that serves a CSV report of the user base as a download.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The CSV file as a download.
   */
  public function downloadUserList(): Response {
    // Check if the CSV report and filename to use for the download are passed
    // in the session.
    $filename = $_SESSION['export_filename'] ?? NULL;
    $temp_filename = $_SESSION['temp_filename'] ?? NULL;
    $csrf_token = $_SESSION['csrf_token'] ?? NULL;

    if (!$filename || !$temp_filename || !$csrf_token) {
      throw new NotFoundHttpException();
    }

    // Validate that the expected filename is passed in the download, to prevent
    // malicious attempts to download other files on the server.
    if (!$this->csrfTokenGenerator->validate($csrf_token, $temp_filename)) {
      throw new NotFoundHttpException();
    }

    $data = file_get_contents($temp_filename);
    if ($data === FALSE) {
      $this->messenger()->addError('The user list could not be retrieved from disk.');
      throw new HttpException(500);
    }

    if ($this->fileSystem->unlink($temp_filename) === FALSE) {
      $this->messenger()->addWarning('The user list could not be deleted from the file system.');
    }

    // Create a response that downloads the data instead of displaying it in the
    // browser.
    $response = new Response($data);
    $content_disposition_header = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
    $response->headers->set('Content-Disposition', $content_disposition_header);
    $response->headers->set('Content-Type', 'text/csv');

    return $response;
  }

}
