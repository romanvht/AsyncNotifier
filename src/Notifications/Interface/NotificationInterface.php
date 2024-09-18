<?php

namespace App\Notifications\Interface;

interface NotificationInterface {
    public function send(): bool;
}
