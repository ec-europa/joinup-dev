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
    $version = $this->state()->get('joinup_eulogin_test.version', '3.1.0');
    if ($version === '3.1.0') {
      $content = $this->getSchemaBlob($version, $this->getDomainTypesV310());
    }
    elseif ($version === '3.2.0') {
      $content = $this->getSchemaBlob($version, $this->getDomainTypesV320());
    }
    else {
      throw new \Exception("Wrong version $version");
    }
    return new Response($content);
  }

  /**
   * Return the EU Login response schema blob.
   *
   * The XML blob is similar to https://ecas.ec.europa.eu/cas/schemas, only the
   * version and the domain type list are variables and passed to the method and
   * injected in the blob so that we can build two versions of the schema.
   *
   * @param string $version
   *   The schema version.
   * @param string $domain_types
   *   The domain types XML blob.
   *
   * @return string
   *   The schema as XML blob.
   */
  protected function getSchemaBlob(string $version, string $domain_types): string {
    return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<xsd:schema targetNamespace="https://ecas.ec.europa.eu/cas/schemas" xmlns="https://ecas.ec.europa.eu/cas/schemas" xmlns:xsd="http://www.w3.org/2001/XMLSchema"
            elementFormDefault="qualified" attributeFormDefault="unqualified" version="{$version}" xml:lang="EN">    <xsd:attributeGroup name="ecasServerAttributeGroup">
        <xsd:annotation>
            <xsd:documentation>ECAS Server environment and date-time information</xsd:documentation>
            <xsd:appinfo>since 3.1.0</xsd:appinfo>
        </xsd:annotation>
        <xsd:attribute name="server" type="xsd:string">
            <xsd:annotation>
                <xsd:documentation>
                    The ECAS Server environment such as PRODUCTION, TEST, LOAD, DEVELOPMENT, etc plus the version
                    and build information.
                </xsd:documentation>
            </xsd:annotation>
        </xsd:attribute>
        <xsd:attribute name="date" type="xsd:dateTime">
            <xsd:annotation>
                <xsd:documentation>The ECAS Server date and time.</xsd:documentation>
            </xsd:annotation>
        </xsd:attribute>
        <xsd:attribute name="version" type="xsd:string">
            <xsd:annotation>
                <xsd:documentation>
                    The ECAS protocol version used in this message.
                </xsd:documentation>
                <xsd:appinfo>since 3.6.0</xsd:appinfo>
            </xsd:annotation>
        </xsd:attribute>
    </xsd:attributeGroup>
    <xsd:element name="serviceResponse">
        <xsd:annotation>
            <xsd:documentation>ECAS response to a Ticket validation request or a ProxyTicket obtention request
            </xsd:documentation>
            <xsd:appinfo>version 4.5.0</xsd:appinfo>
        </xsd:annotation>
        <xsd:complexType>
            <xsd:choice>
                <xsd:element name="authenticationSuccess" type="authenticationSuccessType">
                    <xsd:unique name="authenticationSuccess-group-uniqueness">
                        <xsd:annotation>
                            <xsd:documentation>
                                The group name must be unique within the authenticationSuccess element.
                            </xsd:documentation>
                        </xsd:annotation>
                        <xsd:selector xpath="groups"/>
                        <xsd:field xpath="group"/>
                    </xsd:unique>
                    <xsd:unique name="authenticationSuccess-proxy-uniqueness">
                        <xsd:annotation>
                            <xsd:documentation>
                                The proxy value must be unique within the authenticationSuccess element.
                            </xsd:documentation>
                        </xsd:annotation>
                        <xsd:selector xpath="proxies"/>
                        <xsd:field xpath="proxy"/>
                    </xsd:unique>
                    <xsd:unique name="authenticationSuccess-extendedAttribute-uniqueness">
                        <xsd:annotation>
                            <xsd:documentation>
                                Each dynamicAttribute must be unique within the authenticationSuccess element.
                            </xsd:documentation>
                        </xsd:annotation>
                        <xsd:selector xpath="extendedAttributes/extendedAttribute"/>
                        <xsd:field xpath="@name"/>
                    </xsd:unique>
                </xsd:element>
                <xsd:element name="authenticationFailure" type="authenticationFailureType"/>
                <xsd:element name="proxySuccess" type="proxySuccessType"/>
                <xsd:element name="proxyFailure" type="proxyFailureType"/>
            </xsd:choice>
            <xsd:attributeGroup ref="ecasServerAttributeGroup"/>
        </xsd:complexType>
    </xsd:element>
    <xsd:complexType name="authenticationSuccessType">
        <xsd:annotation>
            <xsd:documentation>ECAS body response when authentication succeeded</xsd:documentation>
        </xsd:annotation>
        <xsd:sequence>
            <xsd:element name="user" type="userType">
                <xsd:annotation>
                    <xsd:documentation>The name of the user authenticated by ECAS, this is the uid, the unique user ID.
                    </xsd:documentation>
                </xsd:annotation>
            </xsd:element>
            <xsd:sequence id="userDetails" minOccurs="0">
                <xsd:element name="registrationLevelVersion" minOccurs="0" maxOccurs="unbounded">
                    <xsd:complexType>
                        <xsd:annotation>
                            <xsd:documentation>The version of the user's credentials used in the authentication method
                                for the requested application security level.
                            </xsd:documentation>
                        </xsd:annotation>
                        <xsd:simpleContent>
                            <xsd:extension base="xsd:string">
                                <xsd:attribute name="level" type="registrationLevelType" use="required">
                                    <xsd:annotation>
                                        <xsd:documentation>The application security level for the version of the user's
                                            credentials
                                        </xsd:documentation>
                                    </xsd:annotation>
                                </xsd:attribute>
                            </xsd:extension>
                        </xsd:simpleContent>
                    </xsd:complexType>
                </xsd:element>
                <xsd:element name="departmentNumber" type="xsd:string" minOccurs="0">
                    <xsd:annotation>
                        <xsd:documentation>The user's department number</xsd:documentation>
                    </xsd:annotation>
                </xsd:element>
                <xsd:element name="email" type="xsd:string" minOccurs="0">
                    <xsd:annotation>
                        <xsd:documentation>The user's email</xsd:documentation>
                    </xsd:annotation>
                </xsd:element>
                <xsd:element name="employeeNumber" type="xsd:string" minOccurs="0" nillable="true">
                    <xsd:annotation>
                        <xsd:documentation>
                            The user's employee number, if she belongs to the European Commission.
                            Null for external users.
                            Some other Institutions may also provide an employee number.
                            The employee number is the PER_ID (unique ID from COMREF).

                            This is different from the ecSysperNumber which is the pers_number, registration number and
                            SYSPER number; ecSysperNumber = pers_number = registration number = SYSPER number. The
                            ecSysperNumber is not returned by ECAS.
                        </xsd:documentation>
                        <xsd:appinfo>since 1.9.1</xsd:appinfo>
                    </xsd:annotation>
                </xsd:element>
                <xsd:element name="employeeType" type="employeeTypeType" minOccurs="0">
                    <xsd:annotation>
                        <xsd:documentation>The user's employeeType</xsd:documentation>
                    </xsd:annotation>
                </xsd:element>
                <xsd:element name="firstName" type="xsd:string" minOccurs="0">
                    <xsd:annotation>
                        <xsd:documentation>The user's firstName</xsd:documentation>
                    </xsd:annotation>
                </xsd:element>
                <xsd:element name="lastName" type="xsd:string" minOccurs="0">
                    <xsd:annotation>
                        <xsd:documentation>The user's lastName</xsd:documentation>
                    </xsd:annotation>
                </xsd:element>
                <xsd:element name="domain" type="domainType" minOccurs="0">
                    <xsd:annotation>
                        <xsd:appinfo>The domain replaces the organisation</xsd:appinfo>
                        <xsd:documentation>The user's domain</xsd:documentation>
                    </xsd:annotation>
                </xsd:element>
                <xsd:element name="domainUsername" type="xsd:string" minOccurs="0">
                    <xsd:annotation>
                        <xsd:documentation>
                            The user's name in her domain or organisation.
                            (This can be different from the "user" value, which is the unique user id at the
                            Commission's.
                            The pair domain and domainUsername is unique within the Commission's.)
                        </xsd:documentation>
                    </xsd:annotation>
                </xsd:element>
                <xsd:element name="telephoneNumber" type="xsd:string" minOccurs="0">
                    <xsd:annotation>
                        <xsd:documentation>The user's telephoneNumber</xsd:documentation>
                    </xsd:annotation>
                </xsd:element>
                <xsd:element name="userManager" type="xsd:string" minOccurs="0" nillable="true">
                    <xsd:annotation>
                        <xsd:documentation>The user's manager. May be null.</xsd:documentation>
                    </xsd:annotation>
                </xsd:element>
                <xsd:element name="timeZone" type="xsd:string" minOccurs="0">
                    <xsd:annotation>
                        <xsd:documentation>
                            The user's timeZone e.g. &quot;GMT+01:00&quot;.
                            This user's attribute is informational and may be inaccurate.
                        </xsd:documentation>
                    </xsd:annotation>
                </xsd:element>
                <xsd:element name="locale" type="xsd:string" minOccurs="0">
                    <xsd:annotation>
                        <xsd:documentation>
                            The user's locale e.g. &quot;en&quot;.
                            This user's attribute is informational and may be inaccurate.
                        </xsd:documentation>
                    </xsd:annotation>
                </xsd:element>
                <xsd:element name="assuranceLevel" type="assuranceLevelType" minOccurs="0">
                    <xsd:annotation>
                        <xsd:documentation>
                            The user's identity assurance level.
                        </xsd:documentation>
                    </xsd:annotation>
                </xsd:element>
                <xsd:element name="uid" type="userType">
                    <xsd:annotation>
                        <xsd:documentation>
                            The uid is the user's unique ID. It has the same value as the value of the "user" element
                            (see above).
                        </xsd:documentation>
                    </xsd:annotation>
                </xsd:element>
                <xsd:element name="orgId" type="xsd:string" minOccurs="0">
                    <xsd:annotation>
                        <xsd:documentation>
                            The orgId is the ID of the user's organisation in the HR system.
                        </xsd:documentation>
                        <xsd:appinfo>since 2.5.0</xsd:appinfo>
                    </xsd:annotation>
                </xsd:element>
                <xsd:element name="teleworkingPriority" type="xsd:boolean" minOccurs="0">
                    <xsd:annotation>
                        <xsd:documentation>
                            A "true" value indicates that the user has priority in teleworking.
                            A "false" value indicates that the user does not have priority in teleworking.
                        </xsd:documentation>
                        <xsd:appinfo>since 4.5.0</xsd:appinfo>
                    </xsd:annotation>
                </xsd:element>
                <xsd:element name="extendedAttributes" minOccurs="0">
                    <xsd:annotation>
                        <xsd:documentation>The user's additional attributes, which can come from an extension for
                            specific needs of a target application.
                        </xsd:documentation>
                        <xsd:appinfo>since 4.0.0</xsd:appinfo>
                    </xsd:annotation>
                    <xsd:complexType>
                        <xsd:sequence>
                            <xsd:element name="extendedAttribute" type="attributeType" maxOccurs="unbounded">
                                <xsd:annotation>
                                    <xsd:documentation>An additional attribute.</xsd:documentation>
                                    <xsd:appinfo>since 4.0.0</xsd:appinfo>
                                </xsd:annotation>
                            </xsd:element>
                        </xsd:sequence>
                    </xsd:complexType>
                </xsd:element>
            </xsd:sequence>
            <xsd:element name="groups" minOccurs="0">
                <xsd:annotation>
                    <xsd:documentation>The list of CUD groups the user belongs to</xsd:documentation>
                </xsd:annotation>
                <xsd:complexType>
                    <xsd:sequence>
                        <xsd:element name="group" type="xsd:string" minOccurs="0" maxOccurs="unbounded"/>
                    </xsd:sequence>
                    <xsd:attribute name="number" type="xsd:nonNegativeInteger" use="required"/>
                </xsd:complexType>
            </xsd:element>
            <xsd:element name="strengths">
                <xsd:annotation>
                    <xsd:documentation>
                        The list of authentication strengths the user is currently authenticated with in her SSO session
                        matching the strengths accepted by the target application.
                    </xsd:documentation>
                </xsd:annotation>
                <xsd:complexType>
                    <xsd:sequence>
                        <xsd:element name="strength" type="strengthType" maxOccurs="unbounded">
                            <xsd:annotation>
                                <xsd:documentation>
                                    A strength with which the user was authenticated by ECAS in her SSO
                                    session matching one of the strengths requested by the target application.
                                </xsd:documentation>
                            </xsd:annotation>
                        </xsd:element>
                    </xsd:sequence>
                    <xsd:attribute name="number" type="xsd:nonNegativeInteger" use="required"/>
                </xsd:complexType>
            </xsd:element>
            <xsd:element name="authenticationFactors" minOccurs="0">
                <xsd:annotation>
                    <xsd:documentation>The list of authentication factors in multi-factor authentications
                    </xsd:documentation>
                </xsd:annotation>
                <xsd:complexType>
                    <xsd:sequence>
                        <xsd:choice maxOccurs="unbounded">
                            <xsd:element name="mobilePhoneNumber" type="xsd:string">
                                <xsd:annotation>
                                    <xsd:documentation>The mobile phone number used as second authentication factor when
                                        using multi-factor authentication including an SMS challenge
                                    </xsd:documentation>
                                </xsd:annotation>
                            </xsd:element>
                            <xsd:element name="moniker" type="xsd:string">
                                <xsd:annotation>
                                    <xsd:documentation>The username in a username/password authentication
                                    </xsd:documentation>
                                </xsd:annotation>
                            </xsd:element>
                            <xsd:element name="storkId" type="xsd:string">
                                <xsd:annotation>
                                    <xsd:documentation>The STORK identifier when the STORK authentication is used
                                    </xsd:documentation>
                                </xsd:annotation>
                            </xsd:element>
                            <xsd:element name="tokenCramId" type="xsd:string">
                                <xsd:annotation>
                                    <xsd:documentation>The serial number of the CRAM hardware token used as second
                                        authentication factor when using multi-factor authentication including a
                                        CRAM hardware token
                                    </xsd:documentation>
                                    <xsd:appinfo>since 4.3.0</xsd:appinfo>
                                </xsd:annotation>
                            </xsd:element>
                            <xsd:element name="tokenId" type="xsd:string">
                                <xsd:annotation>
                                    <xsd:documentation>The serial number of the hardware token used as second
                                        authentication factor when using multi-factor authentication including a
                                        hardware token
                                    </xsd:documentation>
                                </xsd:annotation>
                            </xsd:element>
                            <xsd:element name="mobileDevice" type="mobileDeviceType">
                                <xsd:annotation>
                                    <xsd:documentation>The mobile device used as second authentication factor when
                                        using multi-factor authentication including a Mobile Device
                                    </xsd:documentation>
                                    <xsd:appinfo>since 5.8.0</xsd:appinfo>
                                </xsd:annotation>
                            </xsd:element>
                        </xsd:choice>
                        <xsd:any namespace="##targetNamespace" minOccurs="0" maxOccurs="unbounded"/>
                    </xsd:sequence>
                    <xsd:attribute name="number" type="xsd:nonNegativeInteger" use="required"/>
                </xsd:complexType>
            </xsd:element>
            <xsd:element name="loginDate" type="xsd:dateTime" minOccurs="0">
                <xsd:annotation>
                    <xsd:documentation>The timeStamp when the user last authenticated to ECAS by supplying her password
                    </xsd:documentation>
                </xsd:annotation>
            </xsd:element>
            <xsd:element name="sso" type="xsd:boolean">
                <xsd:annotation>
                    <xsd:documentation>
                        A "true" value indicates that the authentication comes from Web Single Sign-On (SSO).
                        A "false" value indicates that the authentication comes from the first
                        authentication of the end-user or from the renewal of the authentication.
                    </xsd:documentation>
                    <xsd:appinfo>since 4.4.0</xsd:appinfo>
                </xsd:annotation>
            </xsd:element>
            <xsd:element name="ticketType" type="ticketType" minOccurs="0">
                <xsd:annotation>
                    <xsd:documentation>
                        The type of the ticket being validated. For instance, the ticket can be a ServiceTicket,
                        a ProxyTicket or a DesktopProxyTicket.
                    </xsd:documentation>
                    <xsd:appinfo>since 1.11.0</xsd:appinfo>
                </xsd:annotation>
            </xsd:element>
            <xsd:element name="proxyGrantingProtocol" type="proxyGrantingProtocolType" minOccurs="0">
                <xsd:annotation>
                    <xsd:documentation>
                        The Proxy Granting Protocol used to obtain Proxy Granting Tickets.
                        For instance, PGT_URL to use a callback URL, CLIENT_CERT to use 2-way SSL and
                        a client X.509 certificate, DESKTOP to request a DesktopProxyGrantingTicket for
                        a desktop application.
                    </xsd:documentation>
                    <xsd:appinfo>since 1.11.0</xsd:appinfo>
                </xsd:annotation>
            </xsd:element>
            <xsd:element name="proxyGrantingTicket" type="xsd:string" minOccurs="0">
                <xsd:annotation>
                    <xsd:documentation>The ProxyGrantingTicket IOU for ECAS proxies (pgtIOU)</xsd:documentation>
                </xsd:annotation>
            </xsd:element>
            <xsd:element name="proxies" minOccurs="0">
                <xsd:annotation>
                    <xsd:documentation>The list of ECAS proxies in the chain</xsd:documentation>
                </xsd:annotation>
                <xsd:complexType>
                    <xsd:sequence>
                        <xsd:element name="proxy" type="xsd:string" minOccurs="0" maxOccurs="unbounded"/>
                    </xsd:sequence>
                    <xsd:attribute name="number" type="xsd:nonNegativeInteger" use="optional"/>
                </xsd:complexType>
            </xsd:element>
        </xsd:sequence>
    </xsd:complexType>
    <xsd:complexType name="authenticationFailureType">
        <xsd:annotation>
            <xsd:documentation>ECAS body response when authentication failed</xsd:documentation>
        </xsd:annotation>
        <xsd:simpleContent>
            <xsd:extension base="xsd:string">
                <xsd:attribute name="code" type="errorCode" use="required">
                    <xsd:annotation>
                        <xsd:documentation>The error code thrown by ECAS</xsd:documentation>
                    </xsd:annotation>
                </xsd:attribute>
            </xsd:extension>
        </xsd:simpleContent>
    </xsd:complexType>
    <xsd:complexType name="proxySuccessType">
        <xsd:annotation>
            <xsd:documentation>ECAS body response when a ProxyTicket request succeeds</xsd:documentation>
        </xsd:annotation>
        <xsd:sequence>
            <xsd:element name="proxyTicket" type="xsd:string">
                <xsd:annotation>
                    <xsd:documentation>The value of the ProxyTicket generated by ECAS</xsd:documentation>
                </xsd:annotation>
            </xsd:element>
        </xsd:sequence>
    </xsd:complexType>
    <xsd:complexType name="proxyFailureType">
        <xsd:annotation>
            <xsd:documentation>ECAS body response when a ProxyTicket request fails</xsd:documentation>
        </xsd:annotation>
        <xsd:simpleContent>
            <xsd:extension base="xsd:string">
                <xsd:attribute name="code" type="errorCode" use="required">
                    <xsd:annotation>
                        <xsd:documentation>The error code thrown by ECAS</xsd:documentation>
                    </xsd:annotation>
                </xsd:attribute>
            </xsd:extension>
        </xsd:simpleContent>
    </xsd:complexType>
    <xsd:simpleType name="userType">
        <xsd:annotation>
            <xsd:documentation>The username should be at least 7-character long</xsd:documentation>
        </xsd:annotation>
        <xsd:restriction base="xsd:string">
            <xsd:minLength value="7"/>
        </xsd:restriction>
    </xsd:simpleType>
    <xsd:simpleType name="errorCode">
        <xsd:annotation>
            <xsd:documentation>Possible error codes thrown by ECAS</xsd:documentation>
        </xsd:annotation>
        <xsd:restriction base="xsd:string">
            <xsd:enumeration value="ACCESSED_DENIED">
                <xsd:annotation>
                    <xsd:documentation>
                        Error code thrown when a ProxyTicket is requested for a target service to which the end-user does not have access.
                    </xsd:documentation>
                    <xsd:appinfo>since 6.3.0</xsd:appinfo>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="BAD_PGT">
                <xsd:annotation>
                    <xsd:documentation>
                        Error code thrown when a ProxyGrantingTicket is not recognized.
                        This may be because the user logged out of ECAS or because the PGT is invalid.
                    </xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="ECAS_PROXY_COMMUNICATION_ERROR">
                <xsd:annotation>
                    <xsd:documentation>
                        Error code thrown when the proxy service callback URL cannot be reached by the ECAS server.
                        This may be because the callback URL is not available (503 or 404) or
                        because this callback URL is wrongly under a security constraint (401) or
                        because the callback URL uses an SSL certificate which is not trusted by the ECAS server or
                        because the callback URL did not reply correctly (HTTP 200 + proxySuccess tag in the body).
                    </xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="INTERNAL_ERROR">
                <xsd:annotation>
                    <xsd:documentation>Error code thrown when an internal error occurred in the ECAS server itself.
                    </xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="INVALID_PROXY_CALLBACK_URL">
                <xsd:annotation>
                    <xsd:documentation>
                        Error code thrown when the specified ProxyGrantingTicket Callback URL is invalid,e.g. because it
                        is malformed.
                    </xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="INVALID_REQUEST">
                <xsd:annotation>
                    <xsd:documentation>Error code thrown when the request is invalid e.g. because parameters are
                        missing.
                    </xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="INVALID_SERVICE">
                <xsd:annotation>
                    <xsd:documentation>Error code thrown when the service given at validation does not match with the
                        service used at authentication.
                    </xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="INVALID_STRENGTH">
                <xsd:annotation>
                    <xsd:documentation>
                        Error code thrown when the requested authentication strength cannot be provided by the targetted
                        ECAS Server,
                        for example because it does not exist on that environment.
                    </xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="INVALID_TICKET">
                <xsd:annotation>
                    <xsd:documentation>
                        Error code thrown when the ticket is not recognized either because it was not emitted
                        by this ECAS server environment, or because it expired, or because it has already been
                        validated.
                    </xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="INVALID_USER">
                <xsd:annotation>
                    <xsd:documentation>
                        Error code thrown when the user does not meet the requirements for the application e.g.
                        because she is self-registered whilst the application only accepts internal users.
                    </xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
        </xsd:restriction>
    </xsd:simpleType>
    <xsd:simpleType name="strengthType">
        <xsd:annotation>
            <xsd:documentation>Possible values for the strength parameter</xsd:documentation>
        </xsd:annotation>
        <xsd:restriction base="xsd:string">
            <xsd:enumeration value="BASIC">
                <xsd:annotation>
                    <xsd:documentation>
                        Authentication strength used by the ECAS mock-up server.
                        This strength will never be used against a production environment.
                    </xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="CLIENT_CERT">
                <xsd:annotation>
                    <xsd:documentation>
                        Authentication strength representing 2-way SSL with a client X.509 certificate.
                    </xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="PASSWORD">
                <xsd:annotation>
                    <xsd:documentation>
                        Default authentication strength in ECAS, using a username/password scheme.
                        Same as the deprecated STRONG strength.
                    </xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="PASSWORD_SMS">
                <xsd:annotation>
                    <xsd:documentation>
                        Multi-factor authentication strength using PASSWORD and SMS.
                        Replaces the deprecated STRONG_SMS strength.
                    </xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="PASSWORD_TOKEN">
                <xsd:annotation>
                    <xsd:documentation>
                        Multi-factor authentication strength using PASSWORD and a hardware-token challenge (OTP).
                        Replaces the deprecated STRONG_TOKEN strength.
                    </xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="PASSWORD_TOKEN_CRAM">
                <xsd:annotation>
                    <xsd:documentation>
                        Multi-factor authentication strength composed of the ECAS password plus a Challenge-Response
                        Authentication Mechanism (CRAM) performed via a hardware token or "DigiPass".
                    </xsd:documentation>
                    <xsd:appinfo>since 4.3.0</xsd:appinfo>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="PASSWORD_SOFTWARE_TOKEN">
                <xsd:annotation>
                    <xsd:documentation>
                        Multi-factor authentication strength using PASSWORD and a challenge-response OTP generated by
                        the ECAS Mobile app via a QR code.
                    </xsd:documentation>
                    <xsd:appinfo>since 3.10.0</xsd:appinfo>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="PASSWORD_MOBILE_APP">
                <xsd:annotation>
                    <xsd:documentation>
                        Multi-factor authentication strength using PASSWORD and the ECAS Mobile app.
                    </xsd:documentation>
                    <xsd:appinfo>since 3.10.0</xsd:appinfo>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="MOBILE_APP">
                <xsd:annotation>
                    <xsd:documentation>
                        Authentication strength using the ECAS Mobile app to authenticate and access an ECAS-protected
                        resource on the device.
                    </xsd:documentation>
                    <xsd:appinfo>since 3.10.0</xsd:appinfo>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="MDM_CERT">
                <xsd:annotation>
                    <xsd:documentation>
                        Authentication strength using the Mobile-Device-Management (MDM) client software certificate.
                    </xsd:documentation>
                    <xsd:appinfo>since 4.0.0</xsd:appinfo>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="SOCIAL_NETWORKS">
                <xsd:annotation>
                    <xsd:documentation>
                        Authentication strength via federation with a social network such as Facebook, Google, Twitter,
                        etc.
                        Reserved for future use.
                    </xsd:documentation>
                    <xsd:appinfo>since 3.2.0</xsd:appinfo>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="STORK">
                <xsd:annotation>
                    <xsd:documentation>
                        Authentication strength used by the STORK project, which is the federation of European national
                        eIDs.
                    </xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="WINDOWS">
                <xsd:annotation>
                    <xsd:documentation>
                        Authentication strength representing the Microsoft Windows authentication method from within the
                        Commission's network.
                    </xsd:documentation>
                    <xsd:appinfo>since 1.16.0</xsd:appinfo>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="STRONG">
                <xsd:annotation>
                    <xsd:documentation>
                        Default authentication strength in ECAS.
                        Deprecated, please use PASSWORD instead.
                    </xsd:documentation>
                    <xsd:appinfo>Deprecated since 3.1.0, use PASSWORD instead.</xsd:appinfo>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="STRONG_SMS">
                <xsd:annotation>
                    <xsd:documentation>
                        Multi-factor authentication strength using PASSWORD and SMS.
                        Deprecated, please use PASSWORD_SMS instead.
                    </xsd:documentation>
                    <xsd:appinfo>since 1.18.0</xsd:appinfo>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="STRONG_TOKEN">
                <xsd:annotation>
                    <xsd:documentation>
                        Multi-factor authentication strength using PASSWORD and TOKEN.
                        Deprecated, please use PASSWORD_TOKEN instead.
                    </xsd:documentation>
                    <xsd:appinfo>since 1.19.0</xsd:appinfo>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="NTLM">
                <xsd:annotation>
                    <xsd:documentation>
                        Authentication strength representing the legacy Microsoft NTLM protocol against the NET1 domain
                        inside the Commission's network.
                        This strength is deprecated and is going to be phased out in the near future.
                    </xsd:documentation>
                    <xsd:appinfo>deprecated since 1.16.0</xsd:appinfo>
                </xsd:annotation>
            </xsd:enumeration>
        </xsd:restriction>
    </xsd:simpleType>
    <xsd:simpleType name="ticketType">
        <xsd:annotation>
            <xsd:documentation>Legal types of tickets.</xsd:documentation>
        </xsd:annotation>
        <xsd:restriction base="xsd:string">
            <xsd:enumeration value="SERVICE">
                <xsd:annotation>
                    <xsd:documentation>Represents a ServiceTicket</xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="PROXY">
                <xsd:annotation>
                    <xsd:documentation>Represents a ProxyTicket</xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="DESKTOP">
                <xsd:annotation>
                    <xsd:documentation>Represents a DesktopProxyTicket</xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="UNKNOWN">
                <xsd:annotation>
                    <xsd:documentation>Reserved for future use</xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
        </xsd:restriction>
    </xsd:simpleType>
    <xsd:simpleType name="employeeTypeType">
        <xsd:annotation>
            <xsd:documentation>Possible values for the employeeType parameter</xsd:documentation>
            <xsd:appinfo>since 1.9</xsd:appinfo>
        </xsd:annotation>
        <xsd:restriction base="xsd:string">
            <xsd:enumeration value="f">
                <xsd:annotation>
                    <xsd:documentation>Full employee</xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="x">
                <xsd:annotation>
                    <xsd:documentation>Intramuros external user</xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="e">
                <xsd:annotation>
                    <xsd:documentation>Extramuros external user</xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="n">
                <xsd:annotation>
                    <xsd:documentation>Extramuros named external user</xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="r">
                <xsd:annotation>
                    <xsd:documentation>Retired</xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="d">
                <xsd:annotation>
                    <xsd:documentation>Beneficiary</xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="i">
                <xsd:annotation>
                    <xsd:documentation>Other institution employee</xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="s">
                <xsd:annotation>
                    <xsd:documentation>Trainee</xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="c">
                <xsd:annotation>
                    <xsd:documentation>Direct contract</xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="g">
                <xsd:annotation>
                    <xsd:documentation>Guest</xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="j">
                <xsd:annotation>
                    <xsd:documentation>Job</xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="v">
                <xsd:annotation>
                    <xsd:documentation>
                        Virtual.
                        Employee type for virtual users, used in federated identities.
                        Virtual users automatically created from Federated third parties such as STORK eIDs or social networks.
                    </xsd:documentation>
                    <xsd:appinfo>since 3.9.0</xsd:appinfo>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="q">
                <xsd:annotation>
                    <xsd:documentation>
                        XF Statutory Link.
                        The XF statutory link type was created by DG HR to cater for all the different types of exceptions such as
                        stagiaires from countries that are less trusted, pensioners that remain active as advisors, etc.
                    </xsd:documentation>
                    <xsd:appinfo>since 4.0.0</xsd:appinfo>
                </xsd:annotation>
            </xsd:enumeration>
        </xsd:restriction>
    </xsd:simpleType>
    <xsd:simpleType name="domainType">
        <xsd:annotation>
            <xsd:documentation>Possible values for the domain parameter</xsd:documentation>
            <xsd:appinfo>since 1.9</xsd:appinfo>
        </xsd:annotation>
        <xsd:restriction base="xsd:string">
          {$domain_types}
        </xsd:restriction>
    </xsd:simpleType>
    <xsd:simpleType name="proxyGrantingProtocolType">
        <xsd:annotation>
            <xsd:documentation>Legal values for the Proxy Granting Protocol.</xsd:documentation>
        </xsd:annotation>
        <xsd:restriction base="xsd:string">
            <xsd:enumeration value="PGT_URL">
                <xsd:annotation>
                    <xsd:documentation>Protocol using a callback URL accessed in SSL.</xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="CLIENT_CERT">
                <xsd:annotation>
                    <xsd:documentation>Protocol using a client X.509 certificate in 2-way SSL.</xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="DESKTOP">
                <xsd:annotation>
                    <xsd:documentation>Protocol for desktop applications.</xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
        </xsd:restriction>
    </xsd:simpleType>
    <xsd:simpleType name="assuranceLevelType">
        <xsd:annotation>
            <xsd:documentation>Identity Assurance Levels.</xsd:documentation>
        </xsd:annotation>
        <xsd:restriction base="xsd:unsignedShort">
            <xsd:enumeration value="0">
                <xsd:annotation>
                    <xsd:documentation>NO_ASSURANCE</xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="10">
                <xsd:annotation>
                    <xsd:documentation>LOW</xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="20">
                <xsd:annotation>
                    <xsd:documentation>MEDIUM</xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="30">
                <xsd:annotation>
                    <xsd:documentation>HIGH</xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="40">
                <xsd:annotation>
                    <xsd:documentation>TOP</xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
        </xsd:restriction>
    </xsd:simpleType>
    <xsd:element name="userConfirmationSignatureRequest">
        <xsd:annotation>
            <xsd:documentation>ECAS response to a UserConfirmation Signature request</xsd:documentation>
            <xsd:appinfo>since 1.5</xsd:appinfo>
        </xsd:annotation>
        <xsd:complexType>
            <xsd:choice>
                <xsd:element name="signatureRequestId" type="xsd:string"/>
                <xsd:element name="signatureRequestFailure" type="signatureRequestFailureType"/>
            </xsd:choice>
            <xsd:attributeGroup ref="ecasServerAttributeGroup"/>
        </xsd:complexType>
    </xsd:element>
    <xsd:complexType name="signatureRequestFailureType">
        <xsd:annotation>
            <xsd:documentation>ECAS body response when the Signature request failed</xsd:documentation>
        </xsd:annotation>
        <xsd:simpleContent>
            <xsd:extension base="xsd:string">
                <xsd:attribute name="code" type="errorCode" use="required">
                    <xsd:annotation>
                        <xsd:documentation>The error code thrown by ECAS</xsd:documentation>
                    </xsd:annotation>
                </xsd:attribute>
            </xsd:extension>
        </xsd:simpleContent>
    </xsd:complexType>
    <xsd:element name="messageAuthenticationSignature">
        <xsd:annotation>
            <xsd:documentation>ECAS response to a Message Authentication Signature request</xsd:documentation>
            <xsd:appinfo>since 1.5</xsd:appinfo>
        </xsd:annotation>
        <xsd:complexType>
            <xsd:sequence>
                <xsd:element name="messageAuthenticationFailure" type="messageAuthenticationFailureType"/>
            </xsd:sequence>
            <xsd:attributeGroup ref="ecasServerAttributeGroup"/>
        </xsd:complexType>
    </xsd:element>
    <xsd:complexType name="messageAuthenticationFailureType">
        <xsd:annotation>
            <xsd:documentation>ECAS body response when the message authentication Signature failed</xsd:documentation>
        </xsd:annotation>
        <xsd:simpleContent>
            <xsd:extension base="xsd:string">
                <xsd:attribute name="code" type="errorCode" use="required">
                    <xsd:annotation>
                        <xsd:documentation>The error code thrown by ECAS</xsd:documentation>
                    </xsd:annotation>
                </xsd:attribute>
            </xsd:extension>
        </xsd:simpleContent>
    </xsd:complexType>
    <xsd:element name="userConfirmationSignature">
        <xsd:annotation>
            <xsd:documentation>ECAS response to a User Confirmation Signature</xsd:documentation>
            <xsd:appinfo>since 1.5</xsd:appinfo>
        </xsd:annotation>
        <xsd:complexType>
            <xsd:sequence>
                <xsd:element name="signatureFailure" type="signatureFailureType"/>
            </xsd:sequence>
            <xsd:attributeGroup ref="ecasServerAttributeGroup"/>
        </xsd:complexType>
    </xsd:element>
    <xsd:complexType name="signatureFailureType">
        <xsd:annotation>
            <xsd:documentation>ECAS body response when the User Confirmation Signature failed</xsd:documentation>
        </xsd:annotation>
        <xsd:simpleContent>
            <xsd:extension base="xsd:string">
                <xsd:attribute name="code" type="errorCode" use="required">
                    <xsd:annotation>
                        <xsd:documentation>The error code thrown by ECAS</xsd:documentation>
                    </xsd:annotation>
                </xsd:attribute>
            </xsd:extension>
        </xsd:simpleContent>
    </xsd:complexType>
    <xsd:element name="loginRequest">
        <xsd:annotation>
            <xsd:documentation>
                ECAS response to a Login Transaction Request.
                Client applications initiate login transaction by sending all their login parameters prior to
                the redirection to the ECAS login page.
                They receive in return a login request ID and a transaction secret.
                The login request ID is passed in the query string when redirecting to the ECAS login page.
                This prevents accidental tampering of the ECAS login URL.
                The transaction secret is used at validation time to prevent man-in-the-middle attacks.
            </xsd:documentation>
            <xsd:appinfo>since 1.9</xsd:appinfo>
        </xsd:annotation>
        <xsd:complexType>
            <xsd:choice>
                <xsd:element name="loginRequestSuccess" type="loginRequestSuccessType"/>
                <xsd:element name="loginRequestFailure" type="loginRequestFailureType"/>
            </xsd:choice>
            <xsd:attributeGroup ref="ecasServerAttributeGroup"/>
        </xsd:complexType>
    </xsd:element>
    <xsd:complexType name="loginRequestSuccessType">
        <xsd:annotation>
            <xsd:documentation>
                ECAS body response with the Login Transaction Request content
                i.e. the loginRequestId and the transaction secret
            </xsd:documentation>
            <xsd:appinfo>since 1.9</xsd:appinfo>
        </xsd:annotation>
        <xsd:sequence>
            <xsd:element name="loginRequestId" type="xsd:string"/>
            <xsd:element name="loginResponseId" type="xsd:string" minOccurs="0"/>
            <xsd:element name="privateServiceTicket" type="xsd:string" minOccurs="0"/>
        </xsd:sequence>
    </xsd:complexType>
    <xsd:complexType name="loginRequestFailureType">
        <xsd:annotation>
            <xsd:documentation>ECAS body response when the Login Transaction Request failed</xsd:documentation>
            <xsd:appinfo>since 1.9</xsd:appinfo>
        </xsd:annotation>
        <xsd:simpleContent>
            <xsd:extension base="xsd:string">
                <xsd:attribute name="code" type="errorCode" use="required">
                    <xsd:annotation>
                        <xsd:documentation>The error code thrown by ECAS</xsd:documentation>
                    </xsd:annotation>
                </xsd:attribute>
            </xsd:extension>
        </xsd:simpleContent>
    </xsd:complexType>
    <xsd:simpleType name="registrationLevelType">
        <xsd:annotation>
            <xsd:documentation>The application security level for the version of the user's credentials.
            </xsd:documentation>
        </xsd:annotation>
        <xsd:restriction base="xsd:unsignedShort">
            <xsd:enumeration value="0">
                <xsd:annotation>
                    <xsd:documentation>No application security</xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="10">
                <xsd:annotation>
                    <xsd:documentation>Low application security</xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="20">
                <xsd:annotation>
                    <xsd:documentation>Medium application security</xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="30">
                <xsd:annotation>
                    <xsd:documentation>High application security</xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
            <xsd:enumeration value="40">
                <xsd:annotation>
                    <xsd:documentation>Top application security</xsd:documentation>
                </xsd:annotation>
            </xsd:enumeration>
        </xsd:restriction>
    </xsd:simpleType>
    <xsd:simpleType name="iPv4AddressType">
        <xsd:annotation>
            <xsd:documentation>
                IPv4 address in the dotted-decimal notation.
            </xsd:documentation>
            <xsd:appinfo>since 3.1.2</xsd:appinfo>
        </xsd:annotation>

        <xsd:restriction base="xsd:string">
            <xsd:pattern
                    value="((25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.){3}(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])"/>
        </xsd:restriction>
    </xsd:simpleType>
    <xsd:complexType name="attributeType">
        <xsd:annotation>
            <xsd:documentation>An attribute, which can have one or more values.</xsd:documentation>
            <xsd:appinfo>since 4.0.0</xsd:appinfo>
        </xsd:annotation>
        <xsd:sequence>
            <xsd:element name="attributeValue" type="xsd:string" maxOccurs="unbounded">
                <xsd:annotation>
                    <xsd:documentation>One of the values of this attribute.</xsd:documentation>
                    <xsd:appinfo>since 4.0.0</xsd:appinfo>
                </xsd:annotation>
            </xsd:element>
        </xsd:sequence>
        <xsd:attribute name="name" type="xsd:string" use="required">
            <xsd:annotation>
                <xsd:documentation>The name of this attribute.</xsd:documentation>
                <xsd:appinfo>since 4.0.0</xsd:appinfo>
            </xsd:annotation>
        </xsd:attribute>
        <xsd:anyAttribute namespace="##other" processContents="lax"/>
    </xsd:complexType>
    <xsd:complexType name="mobileDeviceType">
        <xsd:annotation>
            <xsd:documentation>
                The mobile device used as second authentication factor when using multi-factor
                authentication including a mobile device such as a smartphone or a tablet.
            </xsd:documentation>
            <xsd:appinfo>since 5.8.0</xsd:appinfo>
        </xsd:annotation>
        <xsd:sequence>
            <xsd:element name="deviceName" type="xsd:string" minOccurs="0">
                <xsd:annotation>
                    <xsd:documentation>The mobile device "friendly" name as chosen by the end-user.</xsd:documentation>
                </xsd:annotation>
            </xsd:element>
            <xsd:element name="deviceIdentifier" type="xsd:string">
                <xsd:annotation>
                    <xsd:documentation>The unique identifier assigned by EU Login to this mobile device.</xsd:documentation>
                </xsd:annotation>
            </xsd:element>
            <xsd:element name="mobileOs" type="xsd:string" minOccurs="0">
                <xsd:annotation>
                    <xsd:documentation>The mobile device operating system (OS).</xsd:documentation>
                </xsd:annotation>
            </xsd:element>
            <xsd:element name="deviceManufacturer" type="xsd:string" minOccurs="0">
                <xsd:annotation>
                    <xsd:documentation>The mobile device manufacturer.</xsd:documentation>
                </xsd:annotation>
            </xsd:element>
            <xsd:element name="deviceModel" type="xsd:string" minOccurs="0">
                <xsd:annotation>
                    <xsd:documentation>The mobile device model.</xsd:documentation>
                </xsd:annotation>
            </xsd:element>
        </xsd:sequence>
        <xsd:anyAttribute namespace="##targetNamespace"/>
    </xsd:complexType>

</xsd:schema>    
XML;
  }

  /**
   * Returns the domain types blob for version 3.1.0 of schema.
   *
   * @return string
   *   The domain types blob.
   */
  protected function getDomainTypesV310(): string {
    return <<<DOMAIN
            <xsd:enumeration value="eu.europa.ec">
                <xsd:annotation>
                    <xsd:documentation>European Commission (3.1.0)</xsd:documentation>
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
DOMAIN;

  }

  /**
   * Returns the domain types blob for version 3.2.0 of schema.
   *
   * @return string
   *   The domain types blob.
   */
  protected function getDomainTypesV320(): string {
    return <<<DOMAIN
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
DOMAIN;
  }

}
