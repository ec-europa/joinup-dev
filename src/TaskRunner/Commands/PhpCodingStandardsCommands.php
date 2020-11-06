<?php

declare(strict_types = 1);

namespace Joinup\TaskRunner\Commands;

use OpenEuropa\TaskRunner\Commands\AbstractCommands;

/**
 * Provides commands for PHP coding standards.
 */
class PhpCodingStandardsCommands extends AbstractCommands {

  /**
   * Setup PHPCS.
   *
   * @command testing:phpcs-setup
   */
  public function phpCsSetup(): void {
    $config = $this->getConfig();

    $document = new \DOMDocument('1.0', 'UTF-8');
    $document->formatOutput = TRUE;

    // Create the root 'ruleset' element.
    $root_element = $document->createElement('ruleset');
    $root_element->setAttribute('name', 'pbm_default');
    $document->appendChild($root_element);

    // Add the description.
    $element = $document->createElement('description', 'Default PHP CodeSniffer configuration for composer based Drupal projects.');
    $root_element->appendChild($element);

    // Add the coding standard.
    $element = $document->createElement('rule');
    $element->setAttribute('ref', $config->get('phpcs.file.standard'));
    $root_element->appendChild($element);

    // Add the files to check.
    foreach ($config->get('phpcs.check.files') as $file) {
      $element = $document->createElement('file', $file);
      $root_element->appendChild($element);
    }

    // Add file extensions.
    if ($config->has('phpcs.check.extensions')) {
      $extensions = implode(',', $config->get('phpcs.check.extensions'));
      $this->appendArgument($document, $root_element, $extensions, 'extensions');
    }

    // Add ignore patterns.
    if ($config->has('phpcs.ignore')) {
      foreach ($config->get('phpcs.ignore') as $pattern) {
        $element = $document->createElement('exclude-pattern', $pattern);
        $root_element->appendChild($element);
      }
    }

    // Add the report type.
    if ($config->has('phpcs.report')) {
      $this->appendArgument($document, $root_element, $config->get('phpcs.report'), 'report');
    }

    if ($config->has('phpcs.options')) {
      $this->appendArgument($document, $root_element, implode('', $config->get('phpcs.options')));
    }

    // Save the file.
    file_put_contents($config->get('phpcs.file.config'), $document->saveXML());

    // If a global configuration file is configured, update this too.
    if ($config->has('phpcs.file.global')) {
      $global_config = <<<PHP
<?php
  \$phpCodeSnifferConfig = [
    'default_standard' => '{$config->get('phpcs.file.config')}',
  ];
PHP;
      file_put_contents($config->get('phpcs.file.global'), $global_config);
    }
  }

  /**
   * Appends an argument element to the XML document.
   *
   * This will append an XML element in the following format:
   * @code
   * <arg name="name" value="value" />
   * @endcode
   * or
   * @code
   * <arg value="value" />
   * @endcode
   *
   * @param \DOMDocument $document
   *   The document that will contain the argument to append.
   * @param \DOMElement $element
   *   The parent element of the argument to append.
   * @param string $value
   *   The value attribute.
   * @param string|null $name
   *   (optional) Name attribute.
   */
  protected function appendArgument(\DOMDocument $document, \DOMElement $element, string $value, ?string $name = NULL): void {
    $argument = $document->createElement('arg');
    if (!empty($name)) {
      $argument->setAttribute('name', $name);
    }
    $argument->setAttribute('value', $value);
    $element->appendChild($argument);
  }

}
