<?php

declare(strict_types=1);

namespace BeycanPress\CryptoPay\MyCred;

// phpcs:disable PSR1.Methods.CamelCapsMethodName
// phpcs:disable Squiz.NamingConventions.ValidVariableName
// phpcs:disable PSR2.Classes.PropertyDeclaration.Underscore
// phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint
// phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint

use BeycanPress\CryptoPay\Payment;
use BeycanPress\CryptoPay\Integrator\Hook;
use BeycanPress\CryptoPay\Integrator\Helpers;
use BeycanPress\CryptoPayLite\Payment as PaymentLite;
use BeycanPress\CryptoPay\Types\Order\OrderType;
use BeycanPress\CryptoPay\Types\Transaction\ParamsType;
use BeycanPress\CryptoPayLite\Types\Order\OrderType as LiteOrderType;
use BeycanPress\CryptoPayLite\Types\Transaction\ParamsType as LiteParamsType;

class BuyCredGateway extends \myCRED_Payment_Gateway
{
    /**
     * @var mixed
     */
    public $prefs;

    /**
     * @var int|float
     */
    public $cost;

    /**
     * @var string
     */
    public $post_id;

    /**
     * @var string
     */
    public $transaction_id;

    /**
     * @var string
     */
    public $currency;

    /**
     * @param mixed $prefs
     * Gateway constructor.
     */
    public function __construct($prefs)
    {
        $types = mycred_get_types();
        $defaultExchange = [];

        foreach ($types as $type => $label) {
            $defaultExchange[$type] = 1;
        }

        parent::__construct([
            'id'               => 'cryptopay',
            'label'            => Helpers::exists() ? 'CryptoPay' : 'CryptoPay Lite',
            'gateway_logo_url' => MYCRED_CRYPTOPAY_URL . 'assets/images/icon.png',
            'defaults' => [
                'theme'    => 'light',
                'currency' => 'USD',
                'exchange' => $defaultExchange
            ]
        ], $prefs);
    }

    /**
     * Preferences (settings)
     * @return void
     */
    public function preferences(): void
    {
        include MYCRED_CRYPTOPAY_DIR . 'views/preferences.php';
    }

    /**
     * Sanitise preferences
     * @param array<mixed> $data
     * @return array<mixed>
     */
    public function sanitise_preferences($data): array
    {
        $newData = [];

        $newData['theme']  = sanitize_text_field($data['theme']);
        $newData['currency']  = sanitize_text_field($data['currency']);

        // If exchange is less then 1 we must start with a zero
        if (isset($data['exchange'])) {
            foreach ((array) $data['exchange'] as $type => $rate) {
                if (1 != $rate && in_array(substr($rate, 0, 1), ['.', ','])) {
                    $data['exchange'][$type] = (float) '0' . $rate;
                }
            }
        }

        $newData['exchange']  = $data['exchange'];

        return $newData;
    }

    /**
     * Payment form frontend side
     * @return void
     */
    public function checkout_page_body(): void
    {
        echo wp_kses_post($this->checkout_header());
        echo wp_kses_post($this->checkout_order());

        $order = [
            'id' => $this->post_id,
            'amount' => floatval($this->cost),
            'currency' => ($this->prefs['currency'] ?? $this->currency) ?? 'USD',
        ];

        $params = [
            'transactionId' => $this->transaction_id
        ];

        Hook::addFilter('theme_mycred_buycred', function (array $theme) {
            $theme['mode'] = $this->prefs['theme'] ?? 'light';
            return $theme;
        });

        if (Helpers::exists()) {
            $cp = (new Payment('mycred_buycred'))
                ->setOrder(OrderType::fromArray($order))
                ->setParams(ParamsType::fromArray($params));
        } else {
            $cp = (new PaymentLite('mycred_buycred'))
                ->setOrder(LiteOrderType::fromArray($order))
                ->setParams(LiteParamsType::fromArray($params));
        }

        Helpers::run('ksesEcho', $cp->html(loading: true));

        echo wp_kses_post($this->checkout_cancel());
    }

    /**
     * Ajax buy process
     * @return void
     */
    public function ajax_buy(): void
    {
        $this->send_json(esc_html__(
            'This gateway does not support AJAX payments.',
            'cryptopay-integration-for-mycred'
        ));
    }

    /**
     * Payment request process
     * @return void
     */
    public function buy(): void
    {
        // Buy
    }

    /**
     * Payment return process
     * @return void
     */
    public function process(): void
    {
        // Process payment
    }
}
