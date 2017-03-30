<?php

/**
 * @file
 * Contains \DrupalProject\build\Phing\AwsS3Get.
 */

namespace DrupalProject\Phing;

use Aws\S3\S3Client;

/**
 * Class AfterFixturesImportCleanup.
 */
class AwsS3Get extends \Task {
  protected $object;
  protected $target;
  protected $bucket;
  protected $key;
  protected $secret;

  /**
   * Fetch a file from S3.
   */
  public function main() {
    $options = [
      'version' => 'latest',
      'region' => 'eu-west-1',
    ];
    // If not set, credentials will be used from the environment.
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
   * @param mixed $object
   */
  public function setObject($object) {
    $this->object = $object;
  }

  /**
   * @param mixed $target
   */
  public function setTarget($target) {
    $this->target = $target;
  }

  /**
   * @param mixed $bucket
   */
  public function setBucket($bucket) {
    $this->bucket = $bucket;
  }

  /**
   * @param mixed $key
   */
  public function setKey($key) {
    $this->key = $key;
  }

  /**
   * @param mixed $secret
   */
  public function setSecret($secret) {
    $this->secret = $secret;
  }



}
