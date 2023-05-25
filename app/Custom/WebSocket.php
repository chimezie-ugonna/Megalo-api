<?php

namespace App\Custom;

use Pusher\Pusher;

class WebSocket
{

    private $pusher;

    function __construct()
    {
        $this->pusher = new Pusher(
            getenv("PUSHER_APP_KEY"),
            getenv("PUSHER_APP_SECRET"),
            getenv("PUSHER_APP_ID"),
            array("cluster" => getenv("PUSHER_APP_CLUSTER"))
        );
    }

    function trigger($data)
    {
        $this->pusher->trigger(getenv("PUSHER_CHANNEL_NAME"), getenv("PUSHER_EVENT_NAME"), $data);
    }
}
