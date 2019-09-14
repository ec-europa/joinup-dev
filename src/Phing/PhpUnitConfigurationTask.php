<?php

/**
 * @file
 * Contains \DrupalProject\build\Phing\PhpUnitConfigurationTask.
 */

namespace DrupalProject\Phing;

require_once 'phing/Task.php';

/**
 * A Phing task to generate a configuration file for PHPUnit.
 */
class PhpUnitConfigurationTask extends \Task {

  /**
   * The path to the template that is used as a basis for the generated file.
   *
   * @var string
   */
  private $distFile = '';

  /**
   * The path to the configuration file to generate.
   *
   * @var string
   */
  private $configFile = '';

  /**
   * Directories containing tests to run.
   *
   * @var array
   */
  private $directories = [];

  /**
   * Test files to run.
   *
   * @var array
   */
  private $files = [];

  /**
   * The name to give to the test suite.
   *
   * @var string
   */
  private $testsuiteName = 'project';

  /**
   * The base URL to use in functional tests.
   *
   * @var string
   */
  private $baseUrl = 'http://localhost';

  /**
   * The database URL to use in kernel tests and functional tests.
   *
   * @var string
   */
  private $dbUrl = 'mysql://root@localhost/db';

  /**
   * The database URL to use in kernel tests and functional tests.
   *
   * @var string
   */
  private $sparqlUrl = 'sparql://localhost:8890/';

  /**
   * The legacy database URL to use in kernel tests and functional tests.
   *
   * @var string
   */
  private $legacyDbUrl = 'mysql://root@localhost/d6_db';

  /**
   * The path to the directory where HTML output from browsertests is stored.
   *
   * @var string
   */
  private $browsertestOutputDirectory = '';

  /**
   * The path to the file that lists HTML output from browsertests.
   *
   * @var string
   */
  private $browsertestOutputFile = '';

  /**
   * The DTT base URL.
   *
   * @var string
   */
  private $dttBaseUrl = '';

  /**
   * The DTT API URL.
   *
   * @var string
   */
  private $dttApiUrl = '';

  /**
   * The DTT Mink driver arguments.
   *
   * @var string
   */
  private $dttMinkDriverArgs = '';

  /**
   * Configures PHPUnit.
   */
  public function main() {
    // Check if all required data is present.
    $this->checkRequirements();

    // Load the template file.
    $document = new \DOMDocument('1.0', 'UTF-8');
    $document->preserveWhiteSpace = FALSE;
    $document->formatOutput = TRUE;
    $document->load($this->distFile);

    // Set the base URL.
    $this->setEnvironmentVariable('SIMPLETEST_BASE_URL', $this->baseUrl, $document);

    // Set the database URL.
    $this->setEnvironmentVariable('SIMPLETEST_DB', $this->dbUrl, $document);

    // Set the database URL.
    $this->setEnvironmentVariable('SIMPLETEST_SPARQL_DB', $this->sparqlUrl, $document);

    // Set the legacy database URL.
    $this->setEnvironmentVariable('SIMPLETEST_LEGACY_DB', $this->legacyDbUrl, $document);

    // Set the path to the browsertest output directory.
    $this->setEnvironmentVariable('BROWSERTEST_OUTPUT_DIRECTORY', $this->browsertestOutputDirectory, $document);

    // Set the path to the browsertest output file.
    $this->setEnvironmentVariable('BROWSERTEST_OUTPUT_FILE', $this->browsertestOutputFile, $document);

    // Set the DTT (Drupal Test Traits) env variables.
    $this->setEnvironmentVariable('DTT_BASE_URL', $this->dttBaseUrl, $document);
    $this->setEnvironmentVariable('DTT_API_URL', $this->dttApiUrl, $document);
    $this->setEnvironmentVariable('DTT_MINK_DRIVER_ARGS', $this->dttMinkDriverArgs, $document);

    // Add a test suite for the Drupal project.
    $test_suite = $document->createElement('testsuite');
    $test_suite->setAttribute('name', $this->testsuiteName);

    // Append the list of test files.
    foreach ($this->files as $file) {
      $element = $document->createElement('file', $file);
      $test_suite->appendChild($element);
    }

    // Append the list of test directories.
    foreach ($this->directories as $directory) {
      $element = $document->createElement('directory', $directory);
      $test_suite->appendChild($element);
    }

    // Insert the test suite in the list of test suites.
    $test_suites = $document->getElementsByTagName('testsuites')->item(0);
    $test_suites->appendChild($test_suite);

    // Save the file.
    file_put_contents($this->configFile, $document->saveXML());
  }

  /**
   * Sets the value of a pre-existing environment variable.
   *
   * @param string $variableName
   *   The name of the environment variable for which to set the value.
   * @param string $value
   *   The value to set.
   * @param \DOMDocument $document
   *   The document in which the change should take place.
   */
  protected function setEnvironmentVariable($variableName, $value, \DOMDocument $document) {
    /** @var \DOMElement $element */
    foreach ($document->getElementsByTagName('env') as $element) {
      if ($element->getAttribute('name') === $variableName) {
        $element->setAttribute('value', $value);
        break;
      }
    }
  }

  /**
   * Checks if all properties required for generating the config are present.
   *
   * @throws \BuildException
   *   Thrown when a required property is not present.
   */
  protected function checkRequirements() {
    $required_properties = ['configFile', 'distFile'];
    foreach ($required_properties as $required_property) {
      if (empty($this->$required_property)) {
        throw new \BuildException("Missing required property '$required_property'.");
      }
    }
  }

  /**
   * Sets the path to the template of the configuration file.
   *
   * @param string $distFile
   *   The path to the template of the configuration file.
   */
  public function setDistFile($distFile) {
    $this->distFile = $distFile;
  }

  /**
   * Sets the path to the configuration file to generate.
   *
   * @param string $configFile
   *   The path to the configuration file to generate.
   */
  public function setConfigFile($configFile) {
    $this->configFile = $configFile;
  }

  /**
   * Sets the list of directories containing test files to execute.
   *
   * @param string $directories
   *   A list of directory paths, delimited by spaces, commas or semicolons.
   */
  public function setDirectories($directories) {
    $this->directories = [];
    $token = ' ,;';
    $directory = strtok($directories, $token);
    while ($directory !== FALSE) {
      $this->directories[] = $directory;
      $directory = strtok($token);
    }
  }

  /**
   * Sets the list of test files to execute.
   *
   * @param string $files
   *   A list of file paths, delimited by spaces, commas or semicolons.
   */
  public function setFiles($files) {
    $this->files = [];
    $token = ' ,;';
    $file = strtok($files, $token);
    while ($file !== FALSE) {
      $this->files[] = $file;
      $file = strtok($token);
    }
  }

  /**
   * Sets the name of the test suite.
   *
   * @param string $testsuiteName
   *   The name of the test suite.
   */
  public function setTestsuiteName($testsuiteName) {
    $this->testsuiteName = $testsuiteName;
  }

  /**
   * Sets the base URL.
   *
   * @param string $baseUrl
   *   The base URL.
   */
  public function setBaseUrl($baseUrl) {
    $this->baseUrl = $baseUrl;
  }

  /**
   * Sets the database URL.
   *
   * @param string $dbUrl
   *   The database URL.
   */
  public function setDbUrl($dbUrl) {
    $this->dbUrl = $dbUrl;
  }

  /**
   * Sets the Sparql URL.
   *
   * @param string $dbUrl
   *   The database URL.
   */
  public function setSparqlUrl($dbUrl) {
    $this->sparqlUrl = $dbUrl;
  }

  /**
   * Sets the legacy DB URL.
   *
   * @param string $legacyDbUrl
   *   The legacy database URL.
   */
  public function setLegacyDbUrl($legacyDbUrl) {
    $this->legacyDbUrl = $legacyDbUrl;
  }

  /**
   * Sets the path to the browsertest output directory.
   *
   * @param string $browsertestOutputDirectory
   *   The path to the directory.
   */
  public function setBrowsertestOutputDirectory($browsertestOutputDirectory) {
    $this->browsertestOutputDirectory = $browsertestOutputDirectory;
  }

  /**
   * Sets the path to the browsertest output file.
   *
   * @param string $browsertestOutputFile
   *   The path to the file.
   */
  public function setBrowsertestOutputFile($browsertestOutputFile) {
    $this->browsertestOutputFile = $browsertestOutputFile;
  }

  /**
   * Sets the DTT base URL.
   *
   * @param string $dttBaseUrl
   *   The DTT base URL.
   */
  public function setDttBaseUrl($dttBaseUrl) {
    $this->dttBaseUrl = $dttBaseUrl;
  }

  /**
   * Sets the DTT API URL.
   *
   * @param string $dttApiUrl
   *   The DTT API URL.
   */
  public function setDttApiUrl($dttApiUrl) {
    $this->dttApiUrl = $dttApiUrl;
  }

  /**
   * Sets the DTT Mink driver arguments.
   *
   * @param string $dttMinkDriverArgs
   *   The DTT Mink driver arguments.
   */
  public function setDttMinkDriverArgs($dttMinkDriverArgs) {
    $this->dttMinkDriverArgs = $dttMinkDriverArgs;
  }

}
