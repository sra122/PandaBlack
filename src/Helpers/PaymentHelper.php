<?php

namespace PandaBlack\Helpers;

use PandaBlack\Helpers\SettingsHelper;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodRepositoryContract;

/**
 * Class PaymentHelper
 */
class PaymentHelper
{
    const PAYMENT_KEY = 'PAYMENT_PANDA_BLACK';

    /**
     * @var PaymentMethodRepositoryContract
     */
    private $paymentMethodRepository;
    /**
     * PrePaymentHelper constructor.
     *
     * @param PaymentMethodRepositoryContract $paymentMethodRepository
     */
    public function __construct(PaymentMethodRepositoryContract $paymentMethodRepository)
    {
        $this->paymentMethodRepository = $paymentMethodRepository;
    }

    /**
     * @return int|null
     */
    public function getPaymentMethodId()
    {
        $paymentMethods = $this->paymentMethodRepository->allForPlugin(SettingsHelper::PLUGIN_NAME);

        foreach($paymentMethods as $paymentMethod)
        {
            return $paymentMethod->id;
        }

        return null;
    }
}