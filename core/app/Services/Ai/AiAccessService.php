<?php

namespace App\Services\Ai;

use App\Models\Topic;
use App\Models\User;
use App\Models\WebmasterSection;

class AiAccessService
{
    public function canManageSettings(?User $user): bool
    {
        return (bool) ($user?->permissionsGroup?->webmaster_status ?? false);
    }

    public function canUse(
        ?User $user,
        WebmasterSection $section,
        ?Topic $topic = null,
    ): bool {
        $group = $user?->permissionsGroup;
        if (!$user || !$group || !$group->ai_use) {
            return false;
        }

        $sections = array_filter(explode(',', (string) $group->data_sections));
        if (!in_array((string) $section->id, $sections, true)) {
            return false;
        }

        if ($topic) {
            if ((int) $topic->webmaster_id !== (int) $section->id || !$group->edit_status) {
                return false;
            }

            return !$group->view_status || (int) $topic->created_by === (int) $user->id;
        }

        return (bool) $group->add_status;
    }
}
