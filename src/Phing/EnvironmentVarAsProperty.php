<?php

declare(strict_types = 1);

namespace DrupalProject\Phing;

/**
 * Phing task to copy prefixed environment vars to phing properties.
 */
class EnvironmentVarAsProperty extends \Task {

  /**
   * The prefix.
   *
   * @var string
   */
  protected $prefix;

  /**
   * Phing property that ends up containing all properties.
   *
   * @var string
   */
  protected $phingproperty;

  /**
   * Converts prefixed environment variables in to prefixed phing properties.
   *
   * CPHP_EXPORTS_S3_SECRET -> cphp.exports.s3.secret.
   */
  public function main() {
    $set = [];
    foreach ($this->project->getProperties() as $defined_property => $value) {
      // Environment variables can be composed of:
      // a-z, A-Z, _ and 0-9
      // but may NOT begin with a number.
      // @see http://pubs.opengroup.org/onlinepubs/009695399/basedefs/xbd_chap08.html
      if (!strstr($defined_property, 'env.' . strtoupper($this->prefix) . '_')) {
        continue;
      }
      $environment_var = preg_replace('/^env.' . strtoupper($this->prefix) . '_/', '', $defined_property);
      // Convert to lowercase, and change underscores to dots.
      $environment_var = strtolower($environment_var);
      $property = str_replace('_', '.', $environment_var);
      $set[$property] = $value;
    }
    $var = '';
    foreach ($set as $key => $value) {
      $var .= $key . ' = ' . $value . "\n";
    }
    if ($var) {
      $this->project->setProperty($this->phingproperty, "\n" . $var);
    }
  }

  /**
   * Sets the prefix.
   *
   * @param string $prefix
   *   The prefix for both the environment variable and phing property name.
   */
  public function setPrefix(string $prefix) {
    $this->prefix = $prefix;
  }

  /**
   * Sets the prefix.
   *
   * @param string $prefix
   *   The prefix for both the environment variable and phing property name.
   */
  public function setPhingProperty(string $prefix) {
    $this->phingproperty = $prefix;
  }

}
