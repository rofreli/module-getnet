<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to https://www.fcamara.com.br/ for more information.
 *
 * @category  FCamara
 * @package   FCamara_Getnet
 * @copyright Copyright (c) 2020 Getnet
 * @Agency    FCamara Formação e Consultoria, Inc. (http://www.fcamara.com.br)
 * @author    Danilo Cavalcanti de Moura <danilo.moura@fcamara.com.br>
 */

namespace FCamara\Getnet\Model;

use FCamara\Getnet\Api\CardsInterface;
use FCamara\Getnet\Model\Client;


class Cards implements CardsInterface
{
    /**
     * @var \FCamara\Getnet\Model\Client
     */
    protected $client;

    /**
     * Cards constructor.
     * @param \FCamara\Getnet\Model\Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param $customerId
     * @return mixed|string
     */
    public function cards($customerId)
    {
        return $this->client->cardList($customerId);
    }
}