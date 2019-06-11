<?php

/**
 * @file
 * Post update functions for the Joinup profile.
 */

declare(strict_types = 1);

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Core\Serialization\Yaml;
use Drupal\field\Entity\FieldConfig;

/**
 * Enable the "Views data export" module.
 */
function joinup_post_update_install_views_data_export(): void {
  \Drupal::service('module_installer')->install(['views_data_export']);
}

/**
 * Enable modules related to geocoding.
 */
function joinup_post_update_install_geocoder(): void {
  $modules = [
    'geocoder',
    'geocoder_geofield',
    'geocoder_field',
    'geofield',
    'oe_webtools_geocoding',
    'oe_webtools_maps',
  ];
  \Drupal::service('module_installer')->install($modules);
}

/**
 * Enable the "Joinup RSS" module.
 */
function joinup_post_update_install_joinup_rss() {
  \Drupal::service('module_installer')->install(['joinup_rss']);
}

/**
 * Enable the "config_readonly" module.
 */
function joinup_post_update_install_config_readonly() {
  \Drupal::service('module_installer')->install(['config_readonly']);
}

/**
 * Allow editing site pages.
 */
function joinup_post_update_site_pages() {
  \Drupal::service('module_installer')->install([
    'block_content',
    'block_content_permissions',
    'page_manager',
  ]);

  // As the configuration synchronization runs after the database post-updates,
  // we manually import the block content type here, in order to be able to
  // create the custom block.
  BlockContentType::create(Yaml::decode(file_get_contents(__DIR__ . '/config/install/block_content.type.simple_block.yml')))->save();
  FieldConfig::create(Yaml::decode(file_get_contents(__DIR__ . '/config/install/field.field.block_content.simple_block.body.yml')))->save();

  $body = <<<BODY
<h2>Important legal notice</h2>
<p>The information on this site is subject to a disclaimer, a copyright and rules related to personal data protection, each in line with the general <a href="http://ec.europa.eu/geninfo/legal_notices_en.htm">European Commission legal notice</a>, and terms of use.</p>
<h2>Copyright notice</h2>
<p>Unless otherwise indicated, reproduction is authorised, except for commercial purposes, provided that the source (Joinup) is acknowledged. Where prior permission must be obtained for the reproduction or use of textual and multimedia information (sound, images, software, etc.), such permission shall cancel the above-mentioned general permission and shall clearly indicate any restrictions on use.</p>
<h3>Special Rules for hosted and federated Open-Source Software projects</h3>
<p>Please note that all the Open-Source Applications (Projects), which are available through the repository on Joinup are provided by their owners (named in each case) subject to the copyright licences indicated in each case; the owners have to certify that all intellectual property rights concerning the Assets belong to them and no intellectual property rights of third parties are infringed. Please refer to the individual project for further information. Please note, that the European Commission accepts no responsibility with regard to these projects.</p>
<h3>Special Rules for interoperability solutions</h3>
<p>Reproduction is not authorized in general for the interoperability solutions. The copyright for the interoperability solutions is defined individually by the licence attached to the individual solution by its owner. Please refer to the individual solution for further information.</p>
<h2>Disclaimer</h2>
<p>The European Commission maintains this website to enhance public access to information about its initiatives and European Union policies in general. Our goal is to keep this information timely and accurate. If errors are brought to our attention, we will try to correct them. However, the Commission accepts no responsibility or liability whatsoever with regard to the information on this site.</p>
<p>This information is:</p>
<ol>
  <li>of a general nature only and is not intended to address the specific circumstances of any particular individual or entity;</li>
  <li>not necessarily comprehensive, complete, accurate or up to date; sometimes linked to external sites over which the Commission services have no control and for which the Commission assumes no responsibility;</li>
  <li>not professional or legal advice (if you need specific advice, you should always consult a suitably qualified professional).</li>
</ol>
<p>Please note that it cannot be guaranteed that a document available on-line exactly reproduces an officially adopted text. Only European Union legislation published in paper editions of the Official Journal of the European Union is deemed authentic.</p>
<p>Please also note that all interoperability solutions, which are available through the repository on Joinup are provided by their owners (named in each case) subject to the licences indicated in each case; the owners have to certify that all intellectual property rights concerning the solutions belong to them and no intellectual property rights of third parties are infringed. The European Commission accepts no responsibility with regard to these solutions.</p>
<p>It is our goal to minimize disruption caused by technical errors. However, some data or information on our site may have been created or structured in files or formats that are not error-free and we cannot guarantee that our service will not be interrupted or otherwise affected by such problems. The Commission accepts no responsibility with regard to such problems incurred as a result of using this site or any linked external sites.</p>
<p>This disclaimer is not intended to limit the liability of the Commission in contravention of any requirements laid down in applicable national law nor to exclude its liability for matters which may not be excluded under that law.</p>
<h2>Privacy Statement</h2>
<h3>The Specific electronic Service: Joinup</h3>
<p>The objective of this portal is to facilitate the development, sharing and re-use of interoperability solutions for public administrations as well as the sharing of best practices in domains relevant to the public sector.</p>
BODY;

  // Create the 'Legal notice' block.
  BlockContent::create([
    'type' => 'simple_block',
    'uuid' => 'ec092d17-ef18-42b0-b460-642871150cd3',
    'status' => TRUE,
    'info' => 'Legal notice',
    'body' => [
      'value' => $body,
      'format' => 'content_editor',
    ],
  ])->save();
}
