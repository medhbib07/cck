<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ChatbotController extends AbstractController
{
    private string $geminiApiKey;

    public function __construct(private HttpClientInterface $client)
    {
        $this->geminiApiKey = $_ENV['GEMINI_API_KEY'];
    }

    #[Route('/chatbot', name: 'chatbot', methods: ['POST'])]
public function ask(Request $request): JsonResponse
{
    try {
        $userMessage = $request->request->get('message');

        $response = $this->client->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent', [

            'query' => ['key' => $this->geminiApiKey],
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => "You are a helpful assistant about the Tunisian CCK. User: $userMessage"
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $data = $response->toArray();
        $botReply = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Sorry, I didn\'t catch that.';

        return new JsonResponse(['reply' => $botReply]);
    } catch (\Throwable $e) {
        return new JsonResponse([
            'reply' => 'Oops! Something went wrong.',
            'error' => $e->getMessage()
        ], 500);
    }
}

}
