<?php

/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to https://www.fcamara.com.br/ for more information.
 *
 * @category  FCamara
 * @package   FCamara_
 * @copyright Copyright (c) 2020 FCamara Formação e Consultoria
 * @Agency    FCamara Formação e Consultoria, Inc. (http://www.fcamara.com.br)
 * @author    Danilo Cavalcanti de Moura <danilo.moura@fcamara.com.br>
 */

namespace FCamara\Getnet\Model\Seller;

use Magento\Checkout\Model\Session;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\ZendClientFactory;
use FCamara\Getnet\Model\Config\SellerConfig;
use FCamara\Getnet\Helper\Data as SellerHelper;

class SellerClientPj
{
    const SUCCESS_CODES = [
        200,
        201,
        202
    ];

    const CONFIG_HTTP_CLIENT = [
        'maxredirects'    => 5,
        'strictredirects' => false,
        'useragent'       => 'Zend_Http_Client',
        'timeout'         => 10,
        'adapter'         => 'Zend_Http_Client_Adapter_Socket',
        'httpversion'     => \Zend_Http_Client::HTTP_1,
        'keepalive'       => false,
        'storeresponse'   => true,
        'strict'          => false,
        'output_stream'   => false,
        'encodecookies'   => true,
        'rfc3986_strict'  => false
    ];

    /**
     * @var SellerConfig
     */
    private $sellerConfig;

    /**
     * @var \Magento\Framework\HTTP\ZendClientFactory
     */
    private $httpClientFactory;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    private $quote;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SellerHelper
     */
    private $sellerHelper;

    /**
     * SellerClientPj constructor.
     * @param ZendClientFactory $httpClientFactory
     * @param SellerConfig $sellerConfig
     * @param Session $session
     * @param LoggerInterface $logger
     * @param SellerHelper $sellerHelper
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function __construct(
        ZendClientFactory $httpClientFactory,
        SellerConfig $sellerConfig,
        Session $session,
        LoggerInterface $logger,
        SellerHelper $sellerHelper
    ) {
        $this->sellerConfig = $sellerConfig;
        $this->httpClientFactory = $httpClientFactory;
        $this->quote = $session->getQuote();
        $this->logger = $logger;
        $this->sellerHelper = $sellerHelper;
    }

    /**
     * @return bool|mixed
     */
    public function authentication()
    {
        $responseBody = false;
        $authorization = base64_encode($this->sellerConfig->clientId() . ':' . $this->sellerConfig->clientSecret());
        $client = $this->httpClientFactory->create();
        $client->setUri($this->sellerConfig->authenticationEndpoint());
        $client->setHeaders(['content-type: application/x-www-form-urlencoded']);
        $client->setHeaders('authorization', 'Basic ' . $authorization);
        $client->setMethod(\Zend_Http_Client::POST);
        $client->setRawData('scope=mgm&grant_type=client_credentials');

        try {
            $responseBody = json_decode($client->request()->getBody(), true);

            if (!isset($responseBody['access_token'])) {
                $responseBody = false;
                throw new \Exception('Can\'t get token');
            }

            $responseBody = $responseBody['access_token'];
        } catch (\Exception $e) {
            $this->logger->critical('Error message', ['exception' => $e]);
        }

        return $responseBody;
    }

    /**
     * @param array $sellerData
     * @return bool|mixed
     */
    public function createSellerPj($sellerData = [])
    {
        $token = $this->authentication();
        $responseBody = false;
        $sellerData['merchant_id'] = $this->sellerConfig->merchantId();

        if (!$token) {
            return $responseBody;
        }

        $data = $this->sellerHelper->createSellerPjArray($sellerData);

        $client = $this->httpClientFactory->create();
        $client->setUri($this->sellerConfig->pjCreatePreSubSellerEndpoint());
        $client->setConfig(self::CONFIG_HTTP_CLIENT);
        $client->setHeaders(['content-type: application/json; charset=utf-8']);
        $client->setHeaders('Authorization', 'Bearer ' . $token);
        $client->setMethod(\Zend_Http_Client::POST);
        $client->setRawData(json_encode($data));

        try {
            $responseBody = json_decode($client->request()->getBody(), true);
            $responseBody['merchant_id'] = $this->sellerConfig->merchantId();
        } catch (\Exception $e) {
            $this->logger->critical('Error message', ['exception' => $e]);
        }

        return $responseBody;
    }

    /**
     * @param array $sellerData
     * @return bool|mixed
     */
    public function pjUpdateSubSeller($sellerData = [])
    {
        $token = $this->authentication();
        $responseBody = false;

        if (!$token) {
            return $responseBody;
        }

        $data = $this->sellerHelper->pjUpdateSubSellerArray($sellerData);

        $client = $this->httpClientFactory->create();
        $client->setUri($this->sellerConfig->pjUpdateSubSellerEndpoint());
        $client->setConfig(self::CONFIG_HTTP_CLIENT);
        $client->setHeaders(['content-type: application/json; charset=utf-8']);
        $client->setHeaders('Authorization', 'Bearer ' . $token);
        $client->setMethod(\Zend_Http_Client::PUT);
        $client->setRawData(json_encode($data));

        try {
            $responseBody = json_decode($client->request()->getBody(), true);
        } catch (\Exception $e) {
            $this->logger->critical('Error message', ['exception' => $e]);
        }

        return $responseBody;
    }

    /**
     * @param array $sellerData
     * @return bool|mixed
     */
    public function pjUpdateComplement($sellerData = [])
    {
        $token = $this->authentication();
        $responseBody = false;

        if (!$token) {
            return $responseBody;
        }

        $data = $this->sellerHelper->pjUpdateComplementArray($sellerData);

        $client = $this->httpClientFactory->create();
        $client->setUri($this->sellerConfig->pfComplementEndpoint());
        $client->setConfig(self::CONFIG_HTTP_CLIENT);
        $client->setHeaders(['content-type: application/json; charset=utf-8']);
        $client->setHeaders('Authorization', 'Bearer ' . $token);
        $client->setMethod(\Zend_Http_Client::PUT);
        $client->setRawData(json_encode($data));

        try {
            $responseBody = json_decode($client->request()->getBody(), true);
        } catch (\Exception $e) {
            $this->logger->critical('Error message', ['exception' => $e]);
        }

        return $responseBody;
    }

    /**
     * @param $subSellerId
     * @return bool|mixed
     */
    public function pjDeAccredit($subSellerId)
    {
        $token = $this->authentication();
        $responseBody = false;

        if (!$token) {
            return $responseBody;
        }

        $client = $this->httpClientFactory->create();
        $client->setUri($this->sellerConfig->pjDeAccreditEndpoint($this->sellerConfig->merchantId(), $subSellerId));
        $client->setConfig(self::CONFIG_HTTP_CLIENT);
        $client->setHeaders(['content-type: application/json; charset=utf-8']);
        $client->setHeaders('Authorization', 'Bearer ' . $token);
        $client->setMethod(\Zend_Http_Client::POST);

        try {
            $responseBody = json_decode($client->request()->getBody(), true);
        } catch (\Exception $e) {
            $this->logger->critical('Error message', ['exception' => $e]);
        }

        return $responseBody;
    }

    /**
     * @param $merchantId
     * @param $cnpj
     * @return bool|mixed
     */
    public function pjCallback($merchantId, $cnpj)
    {
        $token = $this->authentication();
        $responseBody = false;

        if (!$token) {
            return $responseBody;
        }

        $client = $this->httpClientFactory->create();
        $client->setUri($this->sellerConfig->pjCallbackEndpoint($merchantId, $cnpj));
        $client->setHeaders('Authorization', 'Bearer ' . $token);
        $client->setMethod(\Zend_Http_Client::GET);

        try {
            $responseBody = json_decode($client->request()->getBody(), true);
        } catch (\Exception $e) {
            $this->logger->critical('Error message', ['exception' => $e]);
        }

        return $responseBody;
    }
}
