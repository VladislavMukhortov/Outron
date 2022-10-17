<?php


namespace App\Http\Services;


use App\Models\City;
use App\Models\Hotel;
use App\Models\Region;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class YaParser
{
    public static function start()
    {
        var_dump('START');
        $regionsAll = Region::where('id', '!=', 1)->get();
        $skips = [0, 500, 1000];
        $client = new Client();
        $counter = 0;
        foreach ($regionsAll as $item) {
            $countEntries = 0;
            foreach ($skips as $skip) {
                $response = $client->request(
                    'GET',
                    'https://search-maps.yandex.ru/v1',
                    [
                        'query' => [
                            'text' => $item->name . ' база отдыха',
                            'type' => 'biz',
                            'lang' => 'ru_RU',
                            'results' => 500,
                            'skip' => $skip,
                            'apikey' => 'ee0815c6-9e8b-470a-a7ac-682fe36421c7',
//                            'apikey' => '4e89d2a2-9ee4-441f-bca3-32a510c02948',
//                            'apikey' => '4360ad0a-ef48-4e12-9bbf-b13ba897c8ea',
//                            'apikey' => '902b52b5-d3f8-4670-b535-7d90a68c9301',
                        ]
                    ]
                );

                $response = json_decode($response->getBody());

                foreach ($response->features as $hotel) {
                    var_dump($counter);
                    $address = $hotel->properties->CompanyMetaData->address ?? $hotel->properties->description;
                    $addressArray = explode(',', $address);
                    $counter++;
                    if ($addressArray[0] != 'Россия') {
                        continue;
                    }
                    $coordinates = $hotel->geometry->coordinates[1] . ',' . $hotel->geometry->coordinates[0];
                    $regionName = trim($addressArray[1]);

                    $region = Region::where('name', $regionName)
                        ->first();

                    $findCity = $addressArray[2] ?? 'None';

                    if (empty($region)){
                        $city = City::where('name', $regionName)
                            ->first();

                        $region = !empty($city->region_id)
                            ? Region::find($city->region_id)
                            : Region::find(1);
                        $findCity = $addressArray[1] ?? 'None';
                    }

                    $filterCity = (str_ends_with($findCity, 'округ') || str_ends_with($findCity, 'район'))
                        ? ($addressArray[3] ?? "None")
                        :  ($addressArray[2] ?? "None");
                    $cityFullName = str_contains($filterCity, 'улица') ? 'None' : trim($filterCity);
                    if ($cityFullName == 'None') {
                        continue;
                    }
                    $cityArray = explode(' ',trim($cityFullName));

                    switch ($cityArray[0]) {
                        case 'село':
                        case 'поселок':
                        case 'посёлок':
                        case 'деревня':
                        case 'станица':
                        case 'город':
                        $cityName =  str_replace($cityArray[0], '', $cityFullName);
                            break;
                    }

                    $city = City::where('name', trim($cityName))
                        ->where('region_id', $region->id)
                        ->first();

                    if (empty($city)) {
                        $city = City::firstOrCreate(
                            ['name' => $cityFullName, 'region_id' => $region->id,],
                            [ 'country_id' => 1]
                        );
                    }

                    if (!empty($region) && !empty($city)
                    ) {
                        $entry = Hotel::firstOrCreate(
                            ['name' => $hotel->properties->name, 'city_id' => $city->id],
                            [
                                'active' => false,
                                'type_id' => 2,
                                'description' => $hotel->properties->name,
                                'country_id' => 1,
                                'region_id' => $region->id,
                                'address' => $address,
                                'coordinates' => $coordinates,
                                'season_id' => 1,
                                'min_days' => 1,
                                'check_in_hour' => 12,
                                'check_out_hour' => 14,
                                'user_id' => 1,
                            ]
                        );

                        $countEntries++;

                        if (!empty($hotel->properties->CompanyMetaData->Phones)) {
                            foreach ($hotel->properties->CompanyMetaData->Phones as $phone) {
                                $entry
                                    ->contacts()
                                    ->firstOrCreate(['value' => $phone->formatted], ['type_id' => 1]);
                            }
                        }

                        if (!empty($hotel->properties->CompanyMetaData->url)) {
                            $entry
                                ->contacts()
                                ->firstOrCreate(
                                    ['value' => $hotel->properties->CompanyMetaData->url],
                                    ['type_id' => 3]
                                );
                        }
                    }
                }
            }
            $logMessage = $item->name . ': ' . $countEntries . ' записей';
            var_dump($logMessage);
            var_dump($counter);
            Log::info($logMessage);
        }
        var_dump('FINISH');
    }

}
