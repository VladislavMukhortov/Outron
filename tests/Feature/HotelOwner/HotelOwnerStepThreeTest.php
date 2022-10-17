<?php

namespace Tests\Feature\HotelOwner;

use App\Models\Hotel;

trait HotelOwnerStepThreeTest
{
    /**
     * @dataProvider dataStepThreeValidations
     */
    public function testStepThreeValidationForCreate(int $code, array $data, array $errors): void
    {
        $response = $this ->postJson(route('api.owner.hotels.upsert'), $data);

        $response->assertStatus($code)->assertJsonFragment([
            'errors' => $errors,
        ]);
    }

    public function dataStepThreeValidations(): array
    {
        return [
            'step_three_field_images_is_array' => [
                422,
                [
                    'status_id' => Hotel::STATUS_ID_DRAFT,
                    'images' => 'string',
                ],
                [
                    'photos' => [
                        'images' => [
                            'Значение поля Фотографии должно быть массивом.',
                        ],
                    ],
                ],
            ],
        ];
    }
}
