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
 *     "user" = @ContextDefinition("entity:user",
 *       label = @Translation("User"),
 *       required = TRUE
 *     )
 *   }
 * )
 *
 */
class UserRole extends CoreUserRole implements RNGConditionInterface {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $roles = user_role_names(TRUE);
    // @todo: update constant in drupal8-beta8 to AccountInterface:: constant
    unset($roles[DRUPAL_AUTHENTICATED_RID]);
    $form['roles']['#title'] = $this->t('When the user has any all of the following roles');
    $form['roles']['#options'] = array_map('\Drupal\Component\Utility\String::checkPlain', $roles);
    $form['roles']['#description'] = $this->t('If you select no roles, the condition will evaluate to TRUE for all logged-in users.');
    $form['negate']['#access'] = FALSE;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if (!$this->configuration['roles']) {
      return $this->t('Any registered user');
    }

    $roles = array_intersect_key(user_role_names(), $this->configuration['roles']);
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

    $roles = $this->configuration['roles'];
    if (count($roles)) {
      foreach ($roles as $role) {
        $group = $query->andConditionGroup();
        $group->condition('roles', $role, '=');
        $query->condition($group);
      }
    }
  }

}
