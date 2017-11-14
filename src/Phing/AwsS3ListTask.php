<?php

declare(strict_types = 1);

namespace DrupalProject\Phing;

/**
 * Phing task to output a list of files from an AWS S3 bucket in a property.
 */
class AwsS3ListTask extends AwsS3Base {

  /**
   * The prefix for the files to list.
   *
   * @var string
   */
  protected $prefix;

  /**
   * The name of the property in which to store the list of objects.
   *
   * @var string
   */
  protected $propertyName;

  /**
   * Retrieves a list of files and stores it in a Phing property.
   */
  public function main() {
    $objects = $this->getS3Client()->listObjects([
      'Bucket' => $this->bucket,
      'Prefix' => $this->prefix,
    ])->toArray()['Contents'];

    // Filter out empty objects, including the containing folder object.
    $objects = array_filter($objects, function ($object) {
      return (bool) $object['Size'];
    });

    // Get the list of object names, stripping the prefix.
    $prefix = $this->prefix;
    $object_names = array_map(function ($object) use ($prefix) {
      return str_replace($prefix, '', $object['Key']);
    }, $objects);

    // Return the object names as a comma separated list.
    $this->project->setProperty($this->propertyName, implode(',', $object_names));
  }

  /**
   * Sets the prefix (the "path") in which to search for files.
   *
   * This will be a value in the format "path/to/folder/". S3 is a flat data
   * store but uses path-like prefixes to simulate a folder structure.
   *
   * @param string $prefix
   *   The prefix for the files to list.
   */
  public function setPrefix(string $prefix) {
    $this->prefix = $prefix;
  }

  /**
   * Sets the name of the property in which to return the object names.
   *
   * @param string $propertyName
   *   The name of the property.
   */
  public function setPropertyName(string $propertyName) {
    $this->propertyName = $propertyName;
  }

}
