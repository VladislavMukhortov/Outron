<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Testing\File;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Tests\TestCase;

class UserTest extends TestCase
{
    use DatabaseMigrations;

    private User $clientAuth;
    private string $clientExistingPhone;

    public function setUp(): void
    {
        parent::setUp();

        $this->clientAuth = User::factory()->asClient()->create();
        $this->userLogin($this->clientAuth->phone);
    }

    public function dataInvalidUpdateRequest(): array
    {
        return [
            'name_not_str' => [
                ['name' => 123],
                ['name'],
            ],
            'name_too_small' => [
                ['name' => 'f'],
                ['name'],
            ],
            'name_not_long' => [
                ['name' => 'foo_bar_foo_bar_foo_bar_foo_bar'],
                ['name'],
            ],
            'avatar_not_image' => [
                ['avatar' => 'foo'],
                ['avatar'],
            ],
            'phone_not_exists' => [
                [],
                ['phone'],
            ],
            'phone_not_string' => [
                ['phone' => 123],
                ['phone'],
            ],
            'phone_too_small' => [
                ['phone' => 'f'],
                ['phone'],
            ],
            'phone_too_long' => [
                ['phone' => 'foo_bar_foo_bar_foo_bar'],
                ['phone'],
            ],
            'phone_already_exists' => [
                ['phone' => '89997776655'],
                ['phone'],
            ],
        ];
    }

    public function dataInvalidChangePhoneRequest(): array
    {
        return [
            'code_not_exists' => [
                [],
                ['code'],
            ],
            'code_not_string' => [
                ['code' => 123123123],
                ['code'],
            ],
        ];
    }

    public function testIndexUser(): void
    {
        $this->getJson(route('api.profile.index'))
            ->assertOk()
            ->assertJsonPath('data.id', $this->clientAuth->getKey())
            ->assertJsonPath('data.name', $this->clientAuth->name)
            ->assertJsonPath('data.email', $this->clientAuth->email)
            ->assertJsonPath(
                'data.avatar',
                $this->clientAuth->getMedia('avatars')->pluck('original_url')->first()
            )
            ->assertJsonPath('data.phone', $this->clientAuth->phone);
    }

    public function testIndexUserNotAuth(): void
    {
        $this->userLogOut();
        $this->getJson(route('api.profile.index'))->assertUnauthorized();
    }

    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function testUpdateUser(): void
    {
        $data = [
            'name' => 'Фёдор',
            'email' => 'fedor@gmail.com',
            'avatar' => File::image('avatar.jpg'),
            'phone' => $this->clientAuth->phone,
        ];

        $this->postJson(route('api.profile.update'), $data)
            ->assertOk()
            ->assertJsonPath('data.id', $this->clientAuth->getKey())
            ->assertJsonPath('data.name', $data['name'])
            ->assertJsonPath('data.email', $data['email']);

        $media = $this->clientAuth->getMedia('avatars');
        $this->assertFileExists($media->first()->getPath());
        $this->assertCount(1, $media);
        $this->assertDatabaseHas('media', [
            'id' => $media->first()->getKey(),
            'model_type' => 'App\\Models\\User',
        ]);
    }

    public function testUpdateUserNotAuth(): void
    {
        $this->userLogOut();
        $this->postJson(route('api.profile.update'))->assertUnauthorized();
    }

    /**
     * @dataProvider dataInvalidUpdateRequest
     */
    public function testUserValidation(array $requestData): void
    {
        User::factory()->asClient()->create(['phone' => '89997776655']);

        $this->postJson(route('api.profile.update'), $requestData)->assertUnprocessable();
    }

    public function testChangePhone(): void
    {
        $data = ['phone' => '323-551-7777'];

        $this->postJson(route('api.profile.update'), $data);

        $this->postJson(route('api.profile.inputChangePhoneCode'), [
            'code' => '11111',
            'phone' => $data['phone'],
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->clientAuth->getKey(),
            'phone' => $data['phone'],
        ]);
    }

    public function testUserCanNotUpdateProfileWithWrongCode(): void
    {
        $phone = '323-551-7777';
        $this->postJson(route('api.profile.update', ['phone' => $phone]));

        $response = $this->postJson(route('api.profile.inputChangePhoneCode'), [
            'code' => 'wrong code',
            'phone' => $phone,
        ]);

        $response->assertUnprocessable();
        $response->assertJson([
            'message' => 'Код не подходит или срок его действия истек.',
            'errors' => [
                'code' => [
                    'Код не подходит или срок его действия истек.',
                ],
            ],
        ]);
    }

    public function testUserCanNotUpdateProfileWithWrongPhone(): void
    {
        $phone = '323-551-7777';
        $this->postJson(route('api.profile.update', ['phone' => $phone]));

        $response = $this ->postJson(route('api.profile.inputChangePhoneCode'), [
            'code' => '11111',
            'phone' => 'wrong phone',
        ]);

        $response->assertUnprocessable();
        $response->assertJson([
            'message' => 'Код не подходит или срок его действия истек.',
            'errors' => [
                'code' => [
                    'Код не подходит или срок его действия истек.',
                ],
            ],
        ]);
    }

    public function testChangePhoneNotAuth()
    {
        $this->userLogOut();
        $this->postJson(route('api.profile.inputChangePhoneCode'), [
            'code' => '11111',
            'phone' => '88888888888',
        ])->assertUnauthorized();
    }

    /**
     * @dataProvider dataInvalidChangePhoneRequest
     */
    public function testChangePhoneValidation(array $requestData)
    {
        $data = ['phone' => '323-551-7777'];

        $this->postJson(route('api.profile.update'), $data);
        $this->postJson(route('api.profile.inputChangePhoneCode'), $requestData)->assertUnprocessable();
    }
}
