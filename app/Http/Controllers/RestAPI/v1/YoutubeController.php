<?php

namespace App\Http\Controllers\RestAPI\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Exception;
use Illuminate\Support\Facades\Http;

class YoutubeController extends Controller
{
    private $clientId = '931091076296-h0klcosot7dd9ueh78ohdm4qqutmmoll.apps.googleusercontent.com';
    private $clientSecret = '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';
    private $refreshToken = '1//04E4gWuiB3fZyCgYIARAAGAQSNwF-L9Iro5Q4I4YAFtPq98vnGm5DfCWUAW-Xzv9tJcn4Yvc5RGn0wBs3w-6x03kMubUNacv1u-k';

    // private $clientId = '383935454926-pforru4qj57b2112p2m9ncga2kf694uf.apps.googleusercontent.com';
    // private $clientSecret = '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';
    // private $refreshToken = '1//04XAQpau1pH62CgYIARAAGAQSNwF-L9IrB_nn71sRRQ6vvmLg0VuAguvv8oTVW-g5JbtTEZGJkWc7MjKqanNXnWPwnGzi06ALuD0';



    // Refresh access token
    private function getAccessToken()
    {
        $client = new Client();
        try {
            $response = $client->post('https://oauth2.googleapis.com/token', [
                'form_params' => [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'refresh_token' => $this->refreshToken,
                    'grant_type' => 'refresh_token',
                ]
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            return $body['access_token'];
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to get access token: ' . $e->getMessage()], 500);
        }
    }

    // Create live broadcast
    public function createLiveBroadcast(Request $request)
    {
        $title = $request->input('title', 'My Live Stream');
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            return response()->json(['error' => 'Unable to authenticate'], 500);
        }

        $client = new Client();

        try {
            $response = $client->post('https://www.googleapis.com/youtube/v3/liveBroadcasts?part=snippet,status', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'snippet' => [
                        'title' => $title,
                        'scheduledStartTime' => now()->toIso8601String(),
                        'scheduledEndTime' => now()->addHour()->toIso8601String(),
                    ],
                    'status' => [
                        'privacyStatus' => 'public',  // Options: public, private, unlisted
                    ],
                ]
            ]);

            $broadcast = json_decode($response->getBody(), true);
            return response()->json(['broadcastId' => $broadcast['id']]);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to create live broadcast: ' . $e->getMessage()], 500);
        }
    }

    // Create live stream
    public function createLiveStream(Request $request)
    {
        $title = $request->input('title', 'My Live Stream');
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            return response()->json(['error' => 'Unable to authenticate'], 500);
        }

        $client = new Client();

        try {
            $response = $client->post('https://www.googleapis.com/youtube/v3/liveStreams?part=snippet,cdn', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'snippet' => [
                        'title' => $title,
                    ],
                    'cdn' => [
                        'frameRate' => '30fps',
                        'resolution' => '720p',
                        'ingestionType' => 'rtmp',
                    ]
                ]
            ]);

            $stream = json_decode($response->getBody(), true);
            return response()->json(['streamId' => $stream['id'], 'ingestionAddress' => $stream['cdn']['ingestionInfo']['ingestionAddress'], 'stream' => json_encode($stream)]);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to create live stream: ' . $e->getMessage()], 500);
        }
    }

    // Bind stream to broadcast
    public function bindLiveBroadcastToStream(Request $request)
    {
        $broadcastId = $request->input('broadcastId');
        $streamId = $request->input('streamId');
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return response()->json(['error' => 'Unable to authenticate'], 500);
        }

        $client = new Client();

        try {
            $response = $client->post('https://www.googleapis.com/youtube/v3/liveBroadcasts/bind', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'query' => [
                    'part' => 'id,snippet',  // Include the part parameter
                    'id' => $broadcastId,     // Broadcast ID as a query parameter
                    'streamId' => $streamId    // Stream ID as a query parameter
                ]
            ]);
            return response()->json(['message' => 'Broadcast bound to stream successfully']);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to bind live broadcast: ' . $e->getMessage()], 500);
        }
    }

    public function stopLiveBroadcast(Request $request)
    {
        $broadcastId = $request->input('broadcastId');
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            return response()->json(['error' => 'Unable to authenticate'], 500);
        }

        $url = "https://www.googleapis.com/youtube/v3/liveBroadcasts?part=status&key={$accessToken}";

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->patch($url, [
            'id' => $broadcastId,
            'status' => [
                'lifeCycleStatus' => 'complete', // Mark as complete to stop the stream
            ],
        ]);

        if ($response->successful()) {
            return response()->json(['message' => 'Stream has been stopped successfully']);
        }

        return response()->json(['message' => 'Failed to stop the stream']);
    }

    public function transitionLiveBroadcast(Request $request)
    {

        $broadcastId = $request->input('broadcastId');
        $status = $request->input('status');

        $client = new Client();
        $accessToken = $this->getAccessToken();

        try {
            $response = $client->post('https://www.googleapis.com/youtube/v3/liveBroadcasts/transition', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'query' => [
                    'part' => 'status',
                    'broadcastStatus' => $status, // Options: testing, live, complete
                    'id' => $broadcastId,
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (Exception $e) {
            throw new Exception('Failed to transition live broadcast: ' . $e->getMessage());
        }
    }

    public function checkStreamHealth($broadcastId)
    {
        try {
            $accessToken = $this->getAccessToken(); // Ensure this function returns a valid OAuth token

            $url = "https://www.googleapis.com/youtube/v3/liveBroadcasts?part=status&id={$broadcastId}";

            // Initialize cURL
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken, // Use OAuth Token instead of API Key
                'Accept: application/json',
            ]);

            $response = curl_exec($ch);

            // Check if cURL request was successful
            if ($response === false) {
                $error_msg = curl_error($ch);
                curl_close($ch);
                throw new \Exception("cURL Error: " . $error_msg);
            }

            curl_close($ch);

            // Decode the API response
            $data = json_decode($response, true);

            // Validate JSON response
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("Invalid JSON response: " . json_last_error_msg());
            }

            // Check if the API returned valid data
            if (isset($data['items']) && count($data['items']) > 0) {
                $status = $data['items'][0]['status']['lifeCycleStatus'];
                return response()->json(['status' => $status]);
            }

            // Handle API errors
            if (isset($data['error'])) {
                \Log::error("YouTube API Error: " . json_encode($data['error']));
                return response()->json([
                    'status' => 'error',
                    'message' => $data['error']['message']
                ], $data['error']['code'] ?? 400);
            }

            return response()->json([
                'status' => 'unknown',
                'message' => 'Broadcast not found or invalid response'
            ], 404);
        } catch (\Exception $e) {
            \Log::error("YouTube API Exception: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}