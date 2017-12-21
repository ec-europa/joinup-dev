<?php

declare(strict_types = 1);

namespace DrupalProject\Phing;

use Aws\S3\S3Client;

/**
 * Base class for Phing tasks that interact with the AWS S3 data store.
 */
abstract class AwsS3Base extends \Task {

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
   * The version of the AWS web service to utilize.
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
   * The AWS S3 client object.
   *
   * @var \Aws\S3\S3ClientInterface
   */
  protected $client;

  /**
   * Returns the AWS S3 client object.
   *
   * @return \Aws\S3\S3Client
   *   The AWS S3 client.
   */
  public function getS3Client() : S3Client {
    if (empty($this->client)) {
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
      $this->client = new S3Client($options);
    }
    return $this->client;
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
   * Sets the version of the AWS web service to utilize.
   *
   * @param string $version
   *   The version of the AWS web service. Defaults to 'latest'.
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
