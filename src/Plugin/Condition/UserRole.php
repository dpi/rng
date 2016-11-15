<?php

namespace Drupal\rng\Plugin\Condition;

use Drupal\user\Plugin\Condition\UserRole as CoreUserRole;
use Drupal\rng\RNGConditionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\Role;

/**
 * Provides a user role condition where all roles are matched.
 *
 * @Condition(
 *   id = "rng_user_role",
 *   label = @Translation("User Role"),
 *   context = {
 *     "user" = @ContextDefinition("entity:user",
 *       label = @Translation("User"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class UserRole extends CoreUserRole implements RNGConditionInterface {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['roles']['#title'] = $this->t('When the user has all of the following roles');
    $form['roles']['#options'] = array_map('\Drupal\Component\Utility\SafeMarkup::checkPlain', $this->getRoles());
    $form['roles']['#description'] = $this->t('If you select no roles, the condition will evaluate to TRUE for all logged-in users.');
    $form['negate']['#access'] = FALSE;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    // Ensure roles in configuration are still existing or valid roles.
    $roles = array_intersect_key($this->getRoles(), $this->configuration['roles']);

    if (!count($roles)) {
      return $this->t('Any registered user');
    }

    return $this->t(
      empty($this->configuration['negate']) ? 'User is a member of @roles' : 'User is not a member of @roles',
      ['@roles' => count($roles) > 1 ? implode(' and ', $roles) : reset($roles)]
    );
  }

  /**
   * {@inheritdoc}
   */
  function alterQuery(&$query) {
    if ($query->getEntityTypeId() != 'user') {
      throw new \Exception('Query only operates on user entity type.');
    }

    // Ensure roles in configuration are still existing or valid roles.
    $roles = array_intersect_key($this->getRoles(), $this->configuration['roles']);
    if (count($roles)) {
      foreach (array_keys($roles) as $role) {
        $group = $query->andConditionGroup();
        $group->condition('roles', $role, '=');
        $query->condition($group);
      }
    }
  }

  /**
   * Get a list of valid roles permitted by global settings.
   *
   * Anonymous and authenticated roles are automatically removed.
   *
   * @return array
   *   An array of role labels keyed by role ID.
   */
  private function getRoles() {
    $options = [];
    foreach (Role::loadMultiple() as $role) {
      /** @var \Drupal\user\RoleInterface $role */
      if ($role->getThirdPartySetting('rng', 'condition_rng_role', FALSE)) {
        $options[$role->id()] = $role->label();
      }
    }

    // If there are no roles enabled, then expose all roles.
    if (!count($options)) {
      $options = user_role_names(TRUE);
    }

    unset($options[AccountInterface::ANONYMOUS_ROLE]);
    unset($options[AccountInterface::AUTHENTICATED_ROLE]);
    return $options;
  }

}
