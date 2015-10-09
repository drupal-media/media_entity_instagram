<?php

/**
 * @file
 * Contains Drupal\media_entity_instagram\Plugin\Validation\ConstraintValidator\InstagramEmbedCodeConstraintValidator.
 */

namespace Drupal\media_entity_instagram\Plugin\Validation\ConstraintValidator;

use Drupal\media_entity_instagram\Plugin\MediaEntity\Type\Instagram;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class InstagramEmbedCodeConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (!isset($entity)) {
      return;
    }

    foreach (Instagram::$validationRegexp as $pattern => $key) {
      if (preg_match($pattern, $entity)) {
        return;
      }
    }

    $this->context->addViolation($constraint->message);
  }

}
