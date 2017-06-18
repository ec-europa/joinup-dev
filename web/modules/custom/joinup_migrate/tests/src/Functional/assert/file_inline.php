<?php

/**
 * @file
 * Assertions for 'file:inline' migration.
 */

// This image is embedded in content of comment with CID 15208 as
// '/sites/default/files/ckeditor_files/images/sd-dss-admin-service-url.png'.
$file = $this->loadEntityByLabel('file', 'public://inline-images/sd-dss-admin-service-url.png');
$this->assertNotEmpty($file);
