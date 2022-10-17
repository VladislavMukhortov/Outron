<?php

namespace Tests\Feature\HotelOwner;

use App\Models\Contact;
use App\Models\Hotel;
use Illuminate\Support\Str;

trait HotelOwnerStepFiveTest
{
    /**
     * @dataProvider dataStepFiveValidations
     */
    public function testStepFiveValidationForCreate(int $code, array $data, array $errors): void
    {
        $response = $this->postJson(route('api.owner.hotels.upsert'), $data);

        $response->assertStatus($code)->assertJsonFragment([
            'errors' => $errors,
        ]);
    }

    public function dataStepFiveValidations(): array
    {
        return [
            'step_five_fields_required' => [
                422,
                [
                    'status_id' => Hotel::STATUS_ID_DRAFT,
                    'country_id' => '',
                    'region_id' => '',
                    'city_id' => '',
                    'address' => '',
                    'distance_city' => '',
                ],
                [
                    'address' => [
                        'country_id'=> [
                            'Поле Страна обязательно для заполнения.'
                        ],
                        'region_id'=> [
                            'Поле Регион обязательно для заполнения.'
                        ],
                        'city_id'=> [
                            'Поле Город обязательно для заполнения.'
                        ],
                        'address'=> [
                            'Поле Адрес обязательно для заполнения.'
                        ],
                        'distance_city'=> [
                            'Поле Расстояние до города обязательно для заполнения.'
                        ],
                    ],
                ],
            ],
            'step_five_country_id_has_correct_value' => [
                422,
                [
                    'status_id' => Hotel::STATUS_ID_DRAFT,
                    'country_id' => 1,
                ],
                [
                    'address' => [
                        'country_id'=> [
                            'Выбранное значение для Страна некорректно.'
                        ],
                    ],
                ],
            ],
            'step_five_region_id_has_correct_value' => [
                422,
                [
                    'status_id' => Hotel::STATUS_ID_DRAFT,
                    'region_id' => 1,
                ],
                [
                    'address' => [
                        'region_id'=> [
                            'Выбранное значение для Регион некорректно.'
                        ],
                    ],
                ],
            ],
            'step_five_city_id_has_correct_value' => [
                422,
                [
                    'status_id' => Hotel::STATUS_ID_DRAFT,
                    'city_id' => 1,
                ],
                [
                    'address' => [
                        'city_id'=> [
                            'Выбранное значение для Город некорректно.'
                        ],
                    ],
                ],
            ],
            'step_five_distance_city_cannot_be_less_than_0' => [
                422,
                [
                    'status_id' => Hotel::STATUS_ID_DRAFT,
                    'distance_city' => -1,
                ],
                [
                    'address' => [
                        'distance_city' => [
                            'Значение поля Расстояние до города должно быть не меньше 0.',
                        ],
                    ],
                ],
            ],
            'step_five_distance_city_cannot_be_more_than_1000000' => [
                422,
                [
                    'status_id' => Hotel::STATUS_ID_DRAFT,
                    'distance_city' => 10000001,
                ],
                [
                    'address' => [
                        'distance_city' => [
                            'Значение поля Расстояние до города не может быть больше 1000000.',
                        ],
                    ],
                ],
            ],
            'step_five_coordinates_cannot_be_less_than_5_symbols' => [
                422,
                [
                    'status_id' => Hotel::STATUS_ID_DRAFT,
                    'coordinates' => Str::random(4),
                ],
                [
                    'address' => [
                        'coordinates' => [
                            'Количество символов в поле Координаты должно быть не меньше 5.',
                        ],
                    ],
                ],
            ],
            'step_five_coordinates_cannot_be_more_than_255_symbols' => [
                422,
                [
                    'status_id' => Hotel::STATUS_ID_DRAFT,
                    'coordinates' => Str::random(256),
                ],
                [
                    'address' => [
                        'coordinates' => [
                            'Количество символов в поле Координаты не может превышать 255.',
                        ],
                    ],
                ],
            ],
        ];
    }
}
