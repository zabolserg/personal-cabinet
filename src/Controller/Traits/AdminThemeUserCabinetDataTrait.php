<?php

namespace App\Controller\Traits;

use App\Entity\User;
use App\Repository\UserRepository;

/**
 * Trait AdminThemeUserCabinetDataTrait
 * @package App\Controller\Traits
 */
trait AdminThemeUserCabinetDataTrait
{
    private $data = [
        'pageTitle' => '',
        'asideMenu' => [
            [
                'itemClassActiveName' => '',
                'itemName' => 'Головна',
                'itemHrefValue' => 'user_cabinet_main_page',
            ],
            [
                'itemClassActiveName' => '',
                'itemName' => 'Нарахування та оплати',
                'itemHrefValue' => 'user_cabinet_accruals_payments_page',
            ],
            [
                'itemClassActiveName' => '',
                'itemName' => 'Показання лічильника',
                'itemHrefValue' => 'user_cabinet_meter_reading_page',
            ],
            [
                'itemClassActiveName' => '',
                'itemName' => 'Мої дані',
                'itemHrefValue' => 'user_cabinet_profile_page',
            ],
        ],
        'headerMenu' => [
            [
                'itemIdName' => 'meter-reading-button',
                'itemName' => 'Внести показники',
                'itemHrefValue' => '#',
            ],
            [
                'itemIdName' => 'invoice-button',
                'itemName' => 'Рахунок',
                'itemHrefValue' => '#',
            ],
            [
                'itemIdName' => 'pay-button',
                'itemName' => 'Оплатити',
                'itemHrefValue' => '#',
            ],
        ],
        'headerTopbar' => [
            [
                'itemIcon' => '',
                'itemText1' => '9.875',
                'itemText2' => 'Поточна ціна',
                'itemClassName' => 'mr-2',
                'itemIdName' => '',
            ],
            [
                'itemIcon' => '',
                'itemText1' => '57.00',
                'itemText2' => 'Поточні показання',
                'itemClassName' => 'mr-2',
                'itemIdName' => '',
            ],
            [
                'itemIcon' => '',
                'itemText1' => '10.97',
                'itemText2' => 'До сплати',
                'itemClassName' => 'mr-2',
                'itemIdName' => '',
            ],
            [
                'itemIcon' => 'fas fa-user-tie',
                'itemText1' => '',
                'itemText2' => 'Профіль',
                'itemClassName' => 'btn ml-4 p-0',
                'itemIdName' => 'kt_quick_user_toggle',
            ],
        ],
        'user' => [],
    ];

    /**
     * @param User $user
     * @return array
     */
    public function getUserInfo(User $user): array
    {
        $name = [];
        if($user->getLastName() != ''){
            array_push($name, $user->getLastName());
        }
        if($user->getFirstName() != ''){
            array_push($name, $user->getFirstName());
        }
        if($user->getPatronymic() != ''){
            array_push($name, $user->getPatronymic());
        }

        $location = [];
        if($user->getPostcode() != 0 && $user->getPostcode() != ''){
            array_push($location, $user->getPostcode());
        }
        if($user->getCity() != ''){
            array_push($location, $user->getCity());
        }
        if($user->getStreet() != ''){
            array_push($location, $user->getStreet());
        }
        if($user->getBuilding() != ''){
            array_push($location, $user->getBuilding());
        }
        if($user->getApartment() != ''){
            array_push($location, $user->getApartment());
        }

        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'status' => User::STATUS_MSG[$user->getStatus()],
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'patronymic' => $user->getPatronymic(),
            'name' => implode(' ', $name),
            'eicCode' => $user->getEicCode(),
            'postcode' => $user->getPostcode(),
            'city' => $user->getCity(),
            'street' => $user->getStreet(),
            'building' => $user->getBuilding(),
            'apartment' => $user->getApartment(),
            'location' => implode(', ', $location),
            'personalAccount' => $user->getPersonalAccount(),
            'homePhone' => $user->getHomePhone(),
            'mobilePhone' => $user->getMobilePhone()
        ];
    }

}
