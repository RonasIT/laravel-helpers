<?php

namespace RonasIT\Support\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;

class SuccessCreateMessage
{
    use SerializesModels;

    public $message;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($message)
    {
        $this->message = $message;
    }
}
