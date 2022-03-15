<?php

namespace App\Http\Jobs;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;


class Submission
{
    /**
     * @var \PhpAmqpLib\Connection\AMQPStreamConnection $connection
     */
    private $connection;

    /**
     * @var \PhpAmqpLib\Channel\AMQPChannel $channel
     */
    private $channel;

    public function _construct()
    {
        $this->connection = new AMQPStreamConnection('127.0.0.1', 5672, 'cisgraderoomcloud', 'cisgraderoom', '/judge');
        $this->channel = $this->connection->channel();
        $this->channel->exchange_declare('cisgraderoom.judge', 'topic');
        $this->channel->queue_declare('cisgraderoom.judge.result', false, false, false, false);
    }

    public function Judge()
    {
        $this->connection = new AMQPStreamConnection('127.0.0.1', 5672, 'cisgraderoomcloud', 'cisgraderoom', '/judge');
        $this->channel = $this->connection->channel();
        $this->channel->exchange_declare('cisgraderoom.judge', 'topic');
        $this->channel->queue_declare('cisgraderoom.judge.result', false, false, false, false);
        $msg = new AMQPMessage('judge');
        $this->channel->basic_publish($msg, 'cisgraderoom.judge', 'cisgraderoom.judge.result.*');
        $this->channel->close();
        $this->connection->close();
    }
}
