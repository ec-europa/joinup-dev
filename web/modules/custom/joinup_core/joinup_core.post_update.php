<?php

/**
 * @file
 * Post update functions for the Joinup core module.
 */

declare(strict_types = 1);

use Drupal\Core\Database\Database;
use Drupal\search_api\Plugin\search_api\datasource\ContentEntity;
use EasyRdf\Graph;
use EasyRdf\GraphStore;
