<?php

/**
 * @file
 * Contains \Drupal\rng\Plugin\Condition\UserRole.
 */

namespace Drupal\rng\Plugin\Condition;

use Drupal\user\Plugin\Condition\UserRole as CoreUserRole;
use Drupal\rng\RNGConditionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a user role condition where all roles are matched.
 *
 * @Condition(
 *   id = "rng_user_role",
 *   label = @Translation("User Role"),
 *   context = {
 *     "user" = @ContextDefinition("entity:user", label = @Translation("User"))
 *   }
 * )
 *
 */
class UserRole extends CoreUserRole implements RNGConditionInterface  {
  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    // @todo: change to AND
    //$form['roles']['#title'] = $this->t('When the user has all of the following roles');
    $form['roles']['#title'] = $this->t('When the user has any one of the following roles');
    $form['roles']['#options'] = array_map('\Drupal\Component\Utility\String::checkPlain', user_role_names(TRUE));
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  function alterQuery(&$query) {
    if ($query->getEntityTypeId() != 'user') {
      throw new \Exception('Query only operates on user entity type.');
    }

    $roles = $this->configuration['roles'];
    unset($roles['authenticated']); // Matches against all users.
    if ($roles) {
      // @todo: change to AND
      $query->condition('roles', $roles, 'IN');
    }
  }
}
