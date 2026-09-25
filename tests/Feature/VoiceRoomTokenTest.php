<?php

namespace Tests\Feature;

use App\Services\AgoraTokenService;
use App\Services\FirestoreVoiceRoomReader;
use App\Support\Agora\AccessToken2;
use App\Support\Agora\ServiceRtc;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Lcobucci\JWT\Token\DataSet;
use Lcobucci\JWT\UnencryptedToken;
use Mockery;
use Tests\TestCase;

class VoiceRoomTokenTest extends TestCase
{
    private const ROOM = 'room1234567890abcdef';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.agora.app_id' => '970CA35de60c44645bbae8a215061b33',
            'services.agora.app_certificate' => '5CFd2fd1755d40ecb72977518be15d3b',
            'services.agora.token_ttl' => 600,
        ]);
    }

    private function authenticateAs(string $uid): void
    {
        $token = Mockery::mock(UnencryptedToken::class);
        $token->shouldReceive('claims')->andReturn(new DataSet(['sub' => $uid], ''));

        $this->mock(FirebaseAuth::class)
            ->shouldReceive('verifyIdToken')
            ->with('good-token', true)
            ->andReturn($token);
    }

    private function fakeRoom(?array $room, ?array $participant): void
    {
        $this->mock(FirestoreVoiceRoomReader::class, function ($mock) use ($room, $participant) {
            $mock->shouldReceive('room')->andReturn($room);
            $mock->shouldReceive('participant')->andReturn($participant);
        });
    }

    private function request(string $roomId = self::ROOM, string $bearer = 'good-token')
    {
        return $this->postJson("/api/voice-rooms/{$roomId}/rtc-token", [], ['Authorization' => "Bearer {$bearer}"]);
    }

    private function rtcService(string $token): ServiceRtc
    {
        $access = new AccessToken2();
        $access->parse($token);

        return $access->getServices(ServiceRtc::SERVICE_TYPE)[0];
    }

    public function test_rejects_requests_without_a_bearer_token(): void
    {
        $this->postJson('/api/voice-rooms/'.self::ROOM.'/rtc-token')->assertStatus(401);
    }

    public function test_rejects_an_invalid_or_revoked_firebase_token(): void
    {
        $this->mock(FirebaseAuth::class)
            ->shouldReceive('verifyIdToken')
            ->andThrow(new \RuntimeException('revoked'));

        $this->request(bearer: 'bad-token')->assertStatus(401);
    }

    public function test_rejects_a_malformed_room_id(): void
    {
        $this->authenticateAs('user-a');

        $this->request('bad room/../id')->assertStatus(404); // slash never reaches the controller
        $this->request(str_repeat('a', 65))->assertStatus(422);
    }

    public function test_unknown_room_is_404(): void
    {
        $this->authenticateAs('user-a');
        $this->fakeRoom(null, null);

        $this->request()->assertStatus(404);
    }

    public function test_ended_room_is_410(): void
    {
        $this->authenticateAs('user-a');
        $this->fakeRoom(['isActive' => false], ['role' => 'host']);

        $this->request()->assertStatus(410);
    }

    public function test_caller_who_has_not_joined_gets_no_token(): void
    {
        $this->authenticateAs('user-a');
        $this->fakeRoom(['isActive' => true], null);

        $this->request()->assertStatus(403);
    }

    public function test_speaker_gets_a_publisher_token_for_their_own_uid(): void
    {
        $this->authenticateAs('user-a');
        $this->fakeRoom(['isActive' => true], ['role' => 'speaker']);

        $response = $this->request()->assertOk()->assertJson([
            'app_id' => '970CA35de60c44645bbae8a215061b33',
            'channel' => self::ROOM,
            'role' => 'publisher',
            'uid' => app(AgoraTokenService::class)->agoraUid('user-a'),
        ]);

        $rtc = $this->rtcService($response->json('token'));
        $this->assertSame(self::ROOM, $rtc->channelName);
        $this->assertArrayHasKey(ServiceRtc::PRIVILEGE_JOIN_CHANNEL, $rtc->privileges);
        $this->assertArrayHasKey(ServiceRtc::PRIVILEGE_PUBLISH_AUDIO_STREAM, $rtc->privileges);
    }

    public function test_listener_gets_a_subscribe_only_token(): void
    {
        $this->authenticateAs('user-b');
        $this->fakeRoom(['isActive' => true], ['role' => 'listener']);

        $response = $this->request()->assertOk()->assertJson(['role' => 'audience']);

        $rtc = $this->rtcService($response->json('token'));
        $this->assertArrayHasKey(ServiceRtc::PRIVILEGE_JOIN_CHANNEL, $rtc->privileges);
        $this->assertArrayNotHasKey(ServiceRtc::PRIVILEGE_PUBLISH_AUDIO_STREAM, $rtc->privileges);
    }

    public function test_host_and_moderator_are_publishers(): void
    {
        foreach (['host', 'moderator'] as $role) {
            $this->authenticateAs('user-c');
            $this->fakeRoom(['isActive' => true], ['role' => $role]);

            $this->request()->assertOk()->assertJson(['role' => 'publisher']);
        }
    }

    public function test_reports_unavailable_when_agora_is_not_configured(): void
    {
        config(['services.agora.app_id' => '', 'services.agora.app_certificate' => '']);
        $this->authenticateAs('user-a');
        $this->fakeRoom(['isActive' => true], ['role' => 'speaker']);

        $this->request()->assertStatus(503);
    }

    public function test_malformed_agora_credentials_fail_instead_of_returning_an_empty_token(): void
    {
        config(['services.agora.app_id' => 'not-32-chars', 'services.agora.app_certificate' => 'also-bad']);
        $this->authenticateAs('user-a');
        $this->fakeRoom(['isActive' => true], ['role' => 'speaker']);

        $this->request()->assertStatus(503);
    }

    public function test_agora_uid_is_stable_positive_and_nonzero(): void
    {
        $service = app(AgoraTokenService::class);

        $this->assertSame($service->agoraUid('abc'), $service->agoraUid('abc'));
        $this->assertGreaterThan(0, $service->agoraUid('abc'));
        $this->assertLessThanOrEqual(0x7FFFFFFF, $service->agoraUid('6xZ8uNt7QyXhwq4z0LGJpw9dXWB3'));
    }
}
