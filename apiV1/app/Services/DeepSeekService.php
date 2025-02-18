<?php
namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class DeepSeekService
{
    protected $client;
    protected $apiKey;
    protected $apiUrl;

    public function __construct()
    {
        $this->apiKey = env('DEEPSEEK_API_KEY');
        $this->apiUrl = env('DEEPSEEK_API_URL', 'https://api.deepseek.com');
        
        if (!$this->apiKey) {
            throw new \Exception('DeepSeek API Key is missing. Check your .env file.');
        }

        $this->client = new Client([
            'base_uri' => $this->apiUrl,
            'headers' => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type'  => 'application/json',
            ],
        ]);
    }

    public function chatCompletion($message)
{
    try {
        $response = $this->client->post('/v1/chat/completions', [
            'json' => [
                'model'    => 'deepseek-chat',  // Change this if needed
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful AI assistant.'],
                    ['role' => 'user', 'content' => $message],
                ],
            ],
        ]);

        $body = json_decode($response->getBody(), true);
        return $body['choices'][0]['message']['content'] ?? 'No response';

    } catch (ClientException $e) {
        // Check for the specific error
        $responseBody = json_decode($e->getResponse()->getBody()->getContents(), true);

        if (isset($responseBody['error']['message']) && strpos($responseBody['error']['message'], 'Insufficient Balance') !== false) {
            return [
                'warning' => 'Due to current server resource constraints, we have temporarily suspended API service recharges to prevent any potential impact on your operations. Existing balances can still be used for calls. We appreciate your understanding!',
            ];
        }

        // For other client exceptions, return a generic error
        return [
            'error' => 'An error occurred with the API call.',
        ];
    }
}
}