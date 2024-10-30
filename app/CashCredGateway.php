<?php

declare(strict_types=1);

namespace BeycanPress\CryptoPay\MyCred;

// phpcs:disable PSR1.Methods.CamelCapsMethodName
// phpcs:disable Squiz.NamingConventions.ValidVariableName
// phpcs:disable PSR2.Classes.PropertyDeclaration.Underscore
// phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint
// phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint

use BeycanPress\CryptoPay\Payment;
use BeycanPress\CryptoPay\Helpers;
use BeycanPress\CryptoPay\PluginHero\Hook;
use BeycanPress\CryptoPay\Types\Order\OrderType;
use BeycanPress\CryptoPay\Types\Network\NetworkType;
use BeycanPress\CryptoPay\Types\Network\NetworksType;
use BeycanPress\CryptoPay\Types\Network\CurrencyType;
use BeycanPress\CryptoPay\Types\Network\CurrenciesType;
use BeycanPress\CryptoPay\Types\Transaction\ParamsType;

class CashCredGateway extends \myCRED_Cash_Payment_Gateway
{
    /**
     * @var mixed
     */
    public $prefs;

    /**
     * @var array<mixed>|null
     */
    private ?array $networks = null;

    /**
     * @var array<mixed>|null
     */
    private ?array $currentNetwork = null;

    /**
     * @var array<mixed>
     */
    private array $currentUserSettings = [];

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
            'label'            => 'CryptoPay',
            'gateway_logo_url' => MYCRED_CRYPTOPAY_URL . 'assets/images/icon.png',
            'gateway_logo_url' => '',
            'defaults'         => [
                'currency'       => 'USD',
                'minimum_amount' => 1,
                'maximum_amount' => 100,
                'exchange'       => $defaultExchange
            ]
        ], $prefs);

        add_action('admin_footer', [$this, 'runCryptoPay']);
    }

    /**
     * Preferences (settings)
     * @return void
     */
    public function preferences(): void
    {
        include MYCRED_CRYPTOPAY_DIR . 'views/cashcred-preferences.php';
    }

    /**
     * Sanitise preferences
     * @param array<mixed> $data
     * @return array<mixed>
     */
    public function sanitise_preferences($data): array
    {
        $newData = [];

        $newData['currency']  = sanitize_text_field($data['currency']);
        $newData['minimum_amount'] = floatval($data['minimum_amount']);
        $newData['maximum_amount'] = floatval($data['maximum_amount']);

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
     * @param int $postId
     * @return array<mixed>
     */
    public function process($postId = false): array
    {
        $time = current_time('mysql');

        update_post_meta($postId, 'cashcred_payment_transfer_date', $time);

        return [
            'date' => $time,
            'status' => true,
            'message' => esc_html__('Payment is completed successfully.', 'mycred')
        ];
    }

    /**
     * @return void
     */
    public function returning(): void
    {
        add_filter('mycred_setup_gateways', [$this, 'relableGateway']);
    }

    /**
     * @return void
     */
    public function admin_init(): void
    {
        add_filter('mycred_setup_gateways', [$this, 'relableGateway']);
    }

    /**
     * @param array<mixed> $installed
     * @return array<mixed>
     */
    public function relableGateway($installed): array
    {
        if (!empty($this->prefs['title']) && $this->prefs['title'] != $installed['cryptopay']['title']) {
            $installed['cryptopay']['title'] = $this->prefs['title'];
        }

        return $installed;
    }

    /**
     * @param string $key
     * @return void
     */
    public function cashcred_payment_settings($key): void
    {
        $settings = cashcred_get_user_payment_details()[$key] ?? [];
        $paymentSettings = cashcred_get_payment_settings(get_the_ID());
        $network = isset($settings['network']) ? json_decode($settings['network'], true) : [];
        $currency = isset($settings['currency']) ? json_decode($settings['currency'], true) : [];
        $address = isset($settings['address']) ? $settings['address'] : '';

        if (is_admin()) {
            $this->currentUserSettings = [
                'network' => $network,
                'currency' => $currency,
                'address' => $address,
                'paymentSettings' => $paymentSettings
            ];
        }

        $this->networks = Helpers::getNetworks()->toArray();

        wp_enqueue_script(
            'cashcred-main',
            MYCRED_CRYPTOPAY_URL . 'assets/js/main.js',
            ['jquery'],
            MYCRED_CRYPTOPAY_VERSION,
            true
        );

        require MYCRED_CRYPTOPAY_DIR . 'views/cashcred.php';
    }

    /**
     * @return void
     */
    public function runCryptoPay(): void
    {
        if (empty($this->currentUserSettings)) {
            return;
        }

        $paymentSettings = $this->currentUserSettings['paymentSettings'];
        $amount = floatval($paymentSettings->points * $paymentSettings->cost);

        $order = [
            'id' => get_the_ID(),
            'amount' => $amount,
            'currency' => $paymentSettings->currency,
        ];

        $params = [
            'receiver' => $this->currentUserSettings['address'],
        ];

        Hook::addFilter('mode_mycred_cashcred', function () {
            return 'network';
        });

        Hook::addFilter('receiver_mycred_cashcred', function () {
            return $this->currentUserSettings['address'];
        });

        Hook::addFilter('edit_networks_mycred_cashcred', function () {
            $network = $this->currentUserSettings['network'];
            $currency = $this->currentUserSettings['currency'];
            $network = NetworkType::fromArray($network);
            $currency = CurrencyType::fromArray($currency);
            $currencies = new CurrenciesType([$currency]);
            $network->setCurrencies($currencies);
            return new NetworksType([$network]);
        });

        Helpers::ksesEcho(
            (new Payment('mycred_cashcred'))
                ->setConfirmation(false)
                ->setOrder(OrderType::fromArray($order))
                ->setParams(ParamsType::fromArray($params))
                ->modal()
        );

        wp_enqueue_script(
            'cashcred-admin',
            MYCRED_CRYPTOPAY_URL . 'assets/js/admin.js',
            ['jquery', Helpers::getProp('mainJsKey')],
            MYCRED_CRYPTOPAY_VERSION,
            true
        );

        wp_localize_script('cashcred-admin', 'mycredCryptoPay', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
        ]);
    }

    /**
     * @param array $network
     * @param array<mixed> $networkItem
     * @return boolean
     */
    private function isSelected(array $network, array $networkItem): bool
    {
        if ($this->currentNetwork) {
            return false;
        }

        if (!isset($network['code'])) {
            $this->currentNetwork = $networkItem;
            return false;
        }

        if ('evmchains' == $networkItem['code']) {
            $res = $networkItem['id'] == $network['id'];
        } else {
            $res = $networkItem['code'] == $network['code'];
        }

        if ($res) {
            $this->currentNetwork = $networkItem;
        }

        return $res;
    }
}
