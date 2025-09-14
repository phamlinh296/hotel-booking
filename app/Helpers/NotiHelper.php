<?php

namespace app\Helpers;

use App\Models\Notification;

class NotiHelper
{
    public static function push($userId, $message)
    {
        Notification::create([
            'user_id' => $userId,
            'message' => $message,
            'is_read' => false,
        ]);
    }
}
