<?php

namespace App\Helper;

use App\Models\User;

class RoleBase
{
    /**
     * @param User $user
     * @return bool
     */
    public function isTeacher(User $user): bool
    {
        if (!empty($user)) {
            return $user->tokenCan('teacher');
        }
        return false;
    }

    /**
     * @param User $user
     * @return bool
     */
    public function isStudent(User $user): bool
    {
        if (!empty($user)) {
            return $user->tokenCan('student');
        }
        return false;
    }

    /**
     * @param User $user
     * @return bool
     */
    public function isAdmin(User $user): bool
    {
        if (!empty($user)) {
            return $user->tokenCan('admin');
        }
        return false;
    }
}
