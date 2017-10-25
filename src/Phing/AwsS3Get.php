<?php

declare(strict_types = 1);

namespace DrupalProject\Phing;

use Aws\S3\S3Client;

/**
 * Phing task to download a file from an AWS S3 bucket.
 */
class AwsS3Get extends \Task {

  /**
   * The name of the S3 bucket.
   *
   * @var string
   */
  protected $bucket;

  /**
   * The AWS region where the bucket is located.
   *
   * @var string
   */
  protected $region;

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
   * The version of the file to download. Defaults to 'latest'.
   *
   * @var string
   */
  protected $version = 'latest';

  /**
   * The authentication key.
   *
   * @var string
   */
  protected $key;

  /**
   * The authentication secret.
   *
   * @var string
   */
  protected $secret;

  /**
   * Fetch a file from S3.
   */
  public function main() {
    $options = [
      'version' => $this->version,
      'region' => $this->region,
    ];
    // If not set, credentials will be retrieved from the environment.
    // @see \Aws\Credentials\CredentialProvider
    if (!empty($this->key) && !empty($this->secret)) {
      $options['credentials'] = [
        'key' => $this->key,
        'secret' => $this->secret
      ];
    }
    $client = new S3Client($options);
    // Save object to a file.
    $client->getObject([
      'Bucket' => $this->bucket,
      'Key' => $this->object,
      'SaveAs' => $this->target
    ]);
  }

  /**
   * Sets the name of the S3 bucket.
   *
   * @param string $bucket
   *   The name of the S3 bucket.
   */
  public function setBucket(string $bucket) {
    $this->bucket = $bucket;
  }

  /**
   * Sets the AWS region where the bucket is located.
   *
   * @param string $region
   *   The AWS region where the bucket is located.
   */
  public function setRegion(string $region) {
    $this->region = $region;
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

  /**
   * Sets the version of the file to download.
   *
   * @param string $version
   *   The version of the file to download. Defaults to 'latest'.
   */
  public function setVersion(string $version = 'latest') {
    $this->version = $version;
  }

  /**
   * Sets the authentication key.
   *
   * @param string $key
   *   The authentication key.
   */
  public function setKey(string $key = NULL) {
    $this->key = $key;
  }

  /**
   * Sets the authentication secret.
   *
   * @param string $secret
   *   The authentication secret.
   */
  public function setSecret(string $secret = NULL) {
    $this->secret = $secret;
  }

}
