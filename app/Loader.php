<?php

declare(strict_types=1);

namespace BeycanPress\CryptoPay\MyCred;

use BeycanPress\CryptoPay\Integrator\Hook;
use BeycanPress\CryptoPay\Integrator\Helpers;

class Loader
{
    /**
     * Loader constructor.
     */
    public function __construct()
    {
        Helpers::registerIntegration('mycred_buycred');
        Helpers::registerIntegration('mycred_cashcred');

        Helpers::createTransactionPage(
            esc_html__('myCred Transactions', 'cryptopay-integration-for-mycred'),
            'mycred_buycred',
            10,
            [],
            ['orderId']
        );

        Hook::addFilter('apply_discount_mycred_cashcred', '__return_false');
        Hook::addAction('payment_finished_mycred_buycred', [$this, 'paymentFinished']);
        Hook::addFilter('payment_redirect_urls_mycred_buycred', [$this, 'paymentRedirectUrls']);

        Hook::addFilter('receiver_mycred_cashcred', function (string $receiver, object $data) {
            if ($data->getParams()->get('receiver')) {
                return $data->getParams()->get('receiver');
            }

            return $receiver;
        }, 10, 2);

        add_filter('mycred_setup_gateways', [$this, 'registerBuyCredGateway']);
        if (Helpers::exists()) {
            add_filter('mycred_cashcred_setup_gateways', [$this, 'registerCashCredGateway']);
        } else {
            add_filter('mycred_cashcred_more_gateways_tab', [$this, 'showCashCredGateway']);
        }
    }

    /**
     * @param object $data
     * @return void
     */
    public function paymentFinished(object $data): void
    {
        $order = $data->getOrder();
        $gateway = buycred_gateway('cryptopay');
        $pendingPayment = $gateway->get_pending_payment($order->getId());
        if ($data->getStatus()) {
            if ($gateway->complete_payment($pendingPayment, $data->getHash())) {
                $gateway->trash_pending_payment($order->getId());
            } else {
                $gateway->log_call($order->getId(), [
                    sprintf(esc_html__('Failed to credit users account.', 'cryptopay-integration-for-mycred'))
                ]);
            }
        } else {
            $gateway->log_call($order->getId(), [
                // translators: %s: transaction hash
                sprintf(__(
                    'Payment not completed. Transaction hash: %s',
                    'cryptopay-integration-for-mycred'
                ), $data->getHash())
            ]);
        }
    }

    /**
     * @param object $data
     * @return array<string>
     */
    public function paymentRedirectUrls(object $data): array
    {
        $gateway = buycred_gateway('cryptopay');
        return [
            'success' => $gateway->get_thankyou(),
            'failed' => $gateway->get_cancelled()
        ];
    }

    /**
     * @param array<mixed> $gateways
     * @return array<mixed>
     */
    public function registerBuyCredGateway(array $gateways): array
    {
        $gateways['cryptopay'] = [
            'title'         => Helpers::exists() ? 'CryptoPay' : 'CryptoPay Lite',
            'documentation' => 'https://beycanpress.gitbook.io/cryptopay-docs/overview/welcome',
            'callback'      => [BuyCredGateway::class],
            'icon'          => 'dashicons-admin-generic',
            'external'      => false,
            'custom_rate'   => true
        ];

        return $gateways;
    }

    /**
     * @param array<mixed> $gateways
     * @return array<mixed>
     */
    public function registerCashCredGateway(array $gateways): array
    {
        $gateways['cryptopay'] = [
            'title'         => 'CryptoPay',
            'documentation' => 'https://beycanpress.gitbook.io/cryptopay-docs/overview/welcome',
            'callback'      => [CashCredGateway::class],
            'icon'          => 'dashicons-admin-generic',
            'external'      => false,
            'custom_rate'   => true
        ];

        return $gateways;
    }

    /**
     * @param array<mixed> $gateways
     * @return array<mixed>
     */
    public function showCashCredGateway(array $gateways): array
    {
        $url = 'https://beycanpress.com/cryptopay/?utm_source=mycred_plugin&utm_medium=show_gateway';
        $gateways['cryptopay'] = [
            'icon'            =>    'dashicons dashicons-admin-generic static',
            'text'            =>    'CryptoPay',
            'additional_text' =>    'Only available in CryptoPay Premium',
            'url'             =>    $url,
            'status'          =>    'disabled',
            'plugin'          =>    'cryptopay/cryptopay.php'
        ];
        return $gateways;
    }
}
