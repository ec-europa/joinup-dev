<?php

/**
 * @file
 * Assertions for 'file:inline' migration.
 */

// This image is embedded in the body of Project with NID 42438 as
// 'ckeditor_files/images/GoogleRefineLogo200-6f445ab5582dc224.png'. We check if
// the path was converted from 'ckeditor_files/images/' to 'inline-images/'.
$file = $this->loadEntityByLabel('file', 'GoogleRefineLogo200-6f445ab5582dc224.png');
$this->assertNotEmpty($file);
$collection = $this->loadEntityByLabel('rdf_entity', 'Collection from Project', 'collection');
$this->assertContains('/files/inline-images/GoogleRefineLogo200-6f445ab5582dc224.png', $collection->get('field_ar_description')->value);

// This file is embedded in the body of Custom Section with NID 74988 as
// 'ckeditor_files/files/CAMSS Change Log v0_4-v1_0.xlsx'. We check if the path
// was converted from 'ckeditor_files/files/' to 'inline-files/'.
$file = $this->loadEntityByLabel('file', 'CAMSS Change Log v0_4-v1_0.xlsx');
$this->assertNotEmpty($file);
$custom_page = $this->loadEntityByLabel('node', 'CAMSS Tools', 'custom_page');
$this->assertContains('/files/inline-files/CAMSS Change Log v0_4-v1_0.xlsx', $custom_page->get('body')->value);
