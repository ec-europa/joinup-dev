<?php

declare(strict_types = 1);

namespace Drupal\joinup_eulogin_test;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mocks the https://ecas.ec.europa.eu/cas/schemas endpoint.
 */
class SchemaEndpointMock extends ControllerBase {

  /**
   * Returns the testing EU Login response schema.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The schema as XML blob.
   *
   * @throws \Exception
   *   If a wrong version has been stored in joinup_eulogin_test.version state.
   */
  public function get(): Response {
    return new Response($this->getSchemaBlob());
  }

  /**
   * Return the EU Login response schema blob.
   *
   * The XML blob is similar to https://ecas.ec.europa.eu/cas/schemas but
   * contains only the parts that are important for testing.
   *
   * @return string
   *   The schema as XML blob.
   */
  protected function getSchemaBlob(): string {
    return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<xsd:schema targetNamespace="https://ecas.ec.europa.eu/cas/schemas" xmlns="https://ecas.ec.europa.eu/cas/schemas" xmlns:xsd="http://www.w3.org/2001/XMLSchema"
            elementFormDefault="qualified" attributeFormDefault="unqualified" version="3.2.0" xml:lang="EN">
    <xsd:simpleType name="domainType">
        <xsd:annotation>
            <xsd:documentation>Possible values for the domain parameter</xsd:documentation>
            <xsd:appinfo>since 1.9</xsd:appinfo>
        </xsd:annotation>
        <xsd:restriction base="xsd:string">
            <xsd:enumeration value="eu.europa.ec">
                <xsd:annotation>
                    <xsd:documentation>European Commission (3.2.0)</xsd:documentation>
                    <xsd:appinfo>since 3.1.0</xsd:appinfo>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="eu.europa.artemis">
                <xsd:annotation>
                    <xsd:documentation>Artemis Joint Undertaking</xsd:documentation>
                    <xsd:appinfo>since 3.6.0</xsd:appinfo>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="eu.europa.berec">
                <xsd:annotation>
                    <xsd:documentation>The BEREC Office</xsd:documentation>
                    <xsd:appinfo>since 3.1.0</xsd:appinfo>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="eu.europa.acme">
                <xsd:annotation>
                    <xsd:documentation>ACME</xsd:documentation>
                    <xsd:appinfo>since 3.2.0</xsd:appinfo>
                </xsd:annotation>
            </xsd:enumeration>
        </xsd:restriction>
    </xsd:simpleType>
</xsd:schema>
XML;
  }

}
