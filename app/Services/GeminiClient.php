<?php
// app/Services/GeminiClient.php

namespace App\Services;

use GuzzleHttp\Client;

class GeminiClient
{
    private Client $http;
    private string $apiKey;
    private string $base;
    private string $ver;
    private string $model;

    public function __construct()
    {
        $this->apiKey = (string) env('GEMINI_API_KEY', '');
        $this->base   = rtrim((string) env('GEMINI_API_BASE', 'https://generativelanguage.googleapis.com'), '/');
        $this->ver    = trim((string) env('GEMINI_API_VERSION', 'v1beta'));
        $this->model  = (string) env('GEMINI_MODEL', 'gemini-2.5-flash');
        $this->http   = new Client([
            'base_uri'        => $this->base,
            'timeout'         => 8.0,   // ↓ nhanh hơn, tránh “đợi mãi”
            'connect_timeout' => 5.0,
            'http_errors'     => false, // không throw 4xx/5xx
        ]);
    }

    public function enabled(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Call Gemini generateContent (single/multi turn).
     * $contents: [ { role: 'user'|'model', parts: [ ['text'=>...], ['functionResponse'=>['name'=>..., 'response'=>[...] ] ] } ] ]
     * $tools: functionDeclarations array
     * $system: system instruction text
     */
    public function generate(array $contents, array $tools = [], ?string $system = null): array
    {
        $body = ['contents' => $contents];
        if ($tools)  $body['tools'] = [['functionDeclarations' => $tools]];
        if ($system) $body['systemInstruction'] = ['parts' => [['text' => $system]]];

        $url = sprintf('/%s/models/%s:generateContent', $this->ver, $this->model);
        try {
            $res = $this->http->post($url, [
                'headers' => ['Content-Type' => 'application/json'],
                'query'   => ['key' => $this->apiKey],
                'json'    => $body,
            ]);
            $json = json_decode((string) $res->getBody(), true);
            return is_array($json) ? $json : ['candidates' => [['content' => ['parts' => [['text' => '']]]]]];
        } catch (\Throwable $e) {
            // Trả khung rỗng để controller rơi về FREE MODE
            return ['candidates' => [['content' => ['parts' => [['text' => '']]]]]];
        }
    }

    public static function firstParts(array $resp): array
    {
        return $resp['candidates'][0]['content']['parts'] ?? [];
    }
}
