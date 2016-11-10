<?php

namespace Drupal\rng;

use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Define a permission generator.
 */
class Permissions {

  use StringTranslationTrait;
  use UrlGeneratorTrait;

  /**
   * Define permissions for proxy registrations by role.
   *
   * @return array
   */
  public function eventProxyRolePermissions() {
    $permissions = [];
    $permissions['rng register self'] = [
      'title' => $this->t('Register self'),
      'description' => $this->t('Can register the logged-in user.'),
    ];

    foreach (user_roles(TRUE) as $role) {
      $role_id = $role->id();
      $t_args = ['%role_name' => $role->label()];
      $permissions["rng register role $role_id"] = [
        'title' => $this->t('Register users with role: %role_name', $t_args),
        'description' => $this->t('Can register other users with this role.'),
      ];
    }

    return $permissions;
  }

}
