<?php

namespace App\Controller\Traits;

use App\Entity\User;
use App\Repository\UserRepository;

/**
 * Trait AdminThemeAdminCabinetDataTrait
 * @package App\Controller\Traits
 */
trait AdminThemeAdminCabinetDataTrait
{
    private $data = [
        'pageTitle' => '',
        'asideMenu' => [
            [
                'itemClassActiveName' => '',
                'itemName' => 'Користувачі',
                'itemHrefValue' => 'admin_cabinet_users_page',
            ],
            [
                'itemClassActiveName' => '',
                'itemName' => 'Документи',
                'itemHrefValue' => 'admin_cabinet_documents_page',
            ],
            [
                'itemClassActiveName' => '',
                'itemName' => 'Лічильники',
                'itemHrefValue' => 'admin_cabinet_meters_page',
            ],
            [
                'itemClassActiveName' => '',
                'itemName' => 'Показання лічильників',
                'itemHrefValue' => 'admin_cabinet_meters_readings_page',
            ],
            [
                'itemClassActiveName' => '',
                'itemName' => 'Мої дані',
                'itemHrefValue' => 'admin_cabinet_profile_page',
            ],
        ],
        'headerMenu' => [],
        'headerTopbar' => [
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
