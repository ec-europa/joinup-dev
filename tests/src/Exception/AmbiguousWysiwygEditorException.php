<?php

declare(strict_types = 1);

namespace Drupal\joinup\Exception;

/**
 * Exception thrown when multiple WYSIWYG editors with the same label are found.
 */
class AmbiguousWysiwygEditorException extends \Exception {
}
