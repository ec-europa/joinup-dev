<?php

declare(strict_types = 1);

namespace DrupalProject\Phing;

/**
 * Phing task to download a file from an AWS S3 bucket.
 */
class AwsS3GetTask extends AwsS3Base {

  /**
   * Optional prefix for the file to download.
   *
   * @var string
   */
  protected $prefix = '';

  /**
   * The path to the file in the S3 bucket to download.
   *
   * @var string
   */
  protected $object;

  /**
   * The local path to the file to save, including filename.
   *
   * @var string
   */
  protected $target;

  /**
   * Fetch a file from S3.
   */
  public function main() {
    // Save object to a file.
    $this->getS3Client()->getObject([
      'Bucket' => $this->bucket,
      'Key' => $this->prefix . $this->object,
      'SaveAs' => $this->target,
    ]);
  }

  /**
   * Sets the prefix to the file in the S3 bucket to download.
   *
   * @param string $prefix
   *   Optional prefix to the file to download.
   */
  public function setPrefix(string $prefix = '') {
    $this->prefix = $prefix;
  }

  /**
   * Sets the path to the file in the S3 bucket to download.
   *
   * @param string $object
   *   The path to the file in the S3 bucket to download.
   */
  public function setObject(string $object) {
    $this->object = $object;
  }

  /**
   * Sets the local path to the file to save, including filename.
   *
   * @param string $target
   *   The local path to the file to save, including filename.
   */
  public function setTarget(string $target) {
    $this->target = $target;
  }

}
