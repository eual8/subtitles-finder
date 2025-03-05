<?php

namespace App\Services;

use App\Models\Video;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class VideoQueue
{
    protected AMQPStreamConnection $connection;

    protected AMQPChannel $channel;

    protected string $queueName;

    public function __construct(protected WhisperService $whisperService)
    {
        $this->queueName = config('rabbitmq.whisper_queue');
    }

    public function addVideo(Video $video): void
    {
        $message = [
            'id' => $video->id,
            'youtubeId' => $video->youtube_id,
        ];

        $this->initConnection();

        $this->channel->queue_declare($this->queueName, false, true, false, false);

        $msg = new AMQPMessage(json_encode($message), ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);

        $this->channel->basic_publish($msg, '', $this->queueName);

        $this->closeConnection();
    }

    public function transcribeQueueVideos(Command $console, $output = null): void
    {
        $this->initConnection();

        $callback = function ($msg) use ($console, $output) {
            $message = json_decode($msg->body, true);

            $console->info('Received - '.$message['youtubeId']);

            $subtitles = $this->whisperService->transcribe($message['youtubeId'], 'ru', $output);

            if ($subtitles) {

                $client = new Client;
                $url = env('MAIN_NODE_URL').'/api/v1/videos/'.$message['id'];

                $data = [
                    'subtitles_autogenerated' => $subtitles,
                ];

                $response = $client->request('PATCH', $url, [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer '.env('MAIN_NODE_TOKEN'),
                    ],
                    'body' => json_encode($data),
                ]);

                $console->info('API answer code - '.$response->getStatusCode());
                $console->info('Transcribed video - '.$message['youtubeId']);
            }

            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };

        $this->channel->basic_qos(null, 1, null); // Принимать только по одному сообщению за раз
        $this->channel->basic_consume($this->queueName, '', false, false, false, false, $callback);

        $console->info('Waiting for videos. To exit press CTRL+C');

        try {
            $this->channel->consume();
        } catch (\Throwable $exception) {
            $console->error($exception->getMessage());
        }
    }

    protected function initConnection(): void
    {
        $this->connection = new AMQPStreamConnection(config('rabbitmq.host'), config('rabbitmq.port'),
            config('rabbitmq.user'), config('rabbitmq.password'), config('rabbitmq.vhost'));

        $this->channel = $this->connection->channel();
    }

    protected function closeConnection(): void
    {
        $this->channel->close();
        $this->connection->close();
    }
}
