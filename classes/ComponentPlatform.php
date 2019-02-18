<?php
/**
 * Created by PhpStorm.
 * User: royalwang
 * Date: 2019-02-18
 * Time: 15:58
 */

namespace Ecjia\App\Theme;


use Ecjia\App\Mobile\ApplicationFactory;
use Ecjia\App\Mobile\Contracts\HomeComponentInterface;

class ComponentPlatform
{

    public static function getPlatformGroups()
    {
        $defaults = [

            [
                'platform' => 'default',
                'label' => '默认全局',
            ],

        ];

        $platforms = self::getHomeComponentPlatform();

        foreach ($platforms as $platform) {
            $defaults[] = [
                'platform' => $platform->getCode(),
                'label' => $platform->getName(),
            ];
        }

//        dd($defaults);
        return $defaults;

    }


    public static function getHomeComponentPlatform()
    {
        $platforms = (new ApplicationFactory())->getPlatformsByContracts(HomeComponentInterface::class);

        return $platforms;
    }


    public static function getPlatformClents($platform)
    {
        if ($platform == 'default') {
            return [];
        }

        $clients = (new ApplicationFactory())->platform($platform)->getClients();

        return $clients;
    }

}