<?php

namespace App\Helper;

use App\Models\Classroom;
use App\Models\User;
use App\Models\UserAccess;

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
            return $user->tokenCan('superteacher');
        }
        return false;
    }

    /**
     * @param User $user
     * @return bool
     */
    public function isTeacherOrAdmin(User $user): bool
    {
        if (!empty($user)) {
            return $user->tokenCan('teacher') || $user->tokenCan('superteacher');
        }
        return false;
    }

    /**
     * @param User $user
     * @return bool
     */
    public function isStudentOrTeacher(User $user): bool
    {
        if (!empty($user)) {
            return $user->tokenCan('student') || $user->tokenCan('teacher');
        }
        return false;
    }

    /**
     * @param User $user
     * @param string $classcode
     * @return bool
     */
    public function checkUserHasPermission(User $user, string $classcode): bool
    {
        if (!empty($user)) {
            return (UserAccess::where('username', $user->username)->where('classcode', $classcode)->where('is_delete', 0)->count() > 0 || Classroom::where('teacher', $user->username)->where('classcode', $classcode)->count() > 0);
        }
        return false;
    }
}
