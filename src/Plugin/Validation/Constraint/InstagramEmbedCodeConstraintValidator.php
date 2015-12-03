<?php

/**
 * @file
 * Contains \Drupal\media_entity_instagram\Plugin\Validation\Constraint\InstagramEmbedCodeConstraintValidator.
 */

namespace Drupal\media_entity_instagram\Plugin\Validation\Constraint;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\media_entity_instagram\Plugin\MediaEntity\Type\Instagram;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the InstagramEmbedCode constraint.
 */
class InstagramEmbedCodeConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    $value = $this->getValue($value);
    if (!isset($value)) {
      return;
    }

    $matches = [];
    foreach (Instagram::$validationRegexp as $pattern => $key) {
      if (preg_match($pattern, $value, $item_matches)) {
        $matches[] = $item_matches;
      }
    }

    if (empty($matches)) {
      $this->context->addViolation($constraint->message);
    }
  }

  /**
   * Extracts the raw value from the validator input.
   *
   * @param mixed $value
   *   The input value. Can be a normal string, or a value wrapped by the
   *   Typed Data API.
   *
   * @return string|null
   */
  protected function getValue($value) {
    if (is_string($value)) {
      return $value;
    }
    elseif ($value instanceof FieldItemInterface) {
      $class = $value->getFieldDefinition()->getClass();
      $property = $class::mainPropertyName();
      if ($property) {
        return $value->get($property);
      }
    }
  }

}
