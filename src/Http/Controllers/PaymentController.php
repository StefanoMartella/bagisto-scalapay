<?php

namespace Webkul\Scalapay\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Webkul\Checkout\Facades\Cart;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Models\Order;
use Scalapay\Sdk\Api;
use Scalapay\Sdk\Model\Api\Client;
use Scalapay\Sdk\Model\Customer\Consumer;
use Scalapay\Sdk\Model\Customer\Contact;
use Scalapay\Sdk\Model\Merchant\MerchantOptions;
use Scalapay\Sdk\Model\Order\OrderDetails;
use Scalapay\Sdk\Model\Order\OrderDetails\Discount;
use Scalapay\Sdk\Model\Order\OrderDetails\Frequency;
use Scalapay\Sdk\Model\Order\OrderDetails\Item;
use Scalapay\Sdk\Model\Order\OrderDetails\Money;

class PaymentController extends Controller
{

    protected $scalapayApi;

    /**
     * Create a new controller instance.
     *
     * @return void
     *
     * @var OrderRepository
     * @var InvoiceRepository
     */
    public function __construct(
        protected OrderRepository $orderRepository,
        protected InvoiceRepository $invoiceRepository,
    ) {
        $scalapayApiKey = core()->getConfigData('sales.payment_methods.scalapay.scalapay_api_key');
        // The api key for testing is always `qhtfs87hjnc12kkos`
        $this->scalapayApi = Api::configure($scalapayApiKey, $scalapayApiKey === 'qhtfs87hjnc12kkos' ? Client::SANDBOX_URI : Client::PRODUCTION_URI);
    }

    /**
     * Redirects to the Scalapay server.
     */
    public function redirect(): RedirectResponse
    {
        $cart = Cart::getCart();
        $billingAddress = $cart->billing_address;
        $billingAddressLines = $this->getAddressLines($billingAddress->address1);

        $shippingRate = $cart->selected_shipping_rate ? $cart->selected_shipping_rate->price : 0;
        $discountAmount = $cart->discount_amount;

        // Scalapay payment

        $consumer = new Consumer();
        $consumer
            ->setEmail($billingAddress->email)
            ->setGivenNames($cart->billing_address->fist_name ?? auth()->user()?->first_name)
            ->setSurname($billingAddress->last_name);

        if (! empty($billingAddress->phone)) {
            $consumer->setPhoneNumber($billingAddress->phone);
        }

        // set Billing Details Contact object
        $billing = new Contact();
        $billing
            ->setName($billingAddress->first_name . ' ' . $billingAddress->last_name)
            ->setLine1(current($billingAddressLines))
            ->setSuburb($billingAddress->city)
            ->setPostcode($billingAddress->postcode)
            ->setCountryCode($billingAddress->country);

        if ($cart->haveStockableItems() && $cart->shipping_address) {
            // set Shipping Details Contact object
            $shipping = new \Scalapay\Sdk\Model\Customer\Contact();
            $shipping
                ->setName($billingAddress->first_name . ' ' . $billingAddress->last_name)
                ->setLine1(current($billingAddressLines))
                ->setSuburb($billingAddress->city)
                ->setPostcode($billingAddress->postcode)
                ->setCountryCode($billingAddress->country);
        }

        // set Item object list
        $itemList = [];
        foreach ($cart->items as $cartItem) {
            $item = new Item();
            $item
                ->setName($cartItem->name)
                ->setSku($cartItem->sku)
                ->setQuantity($cartItem->quantity);
            $itemPrice = new Money();
            $itemPrice->setAmount($this->formatCurrencyValue($cartItem->price));
            $item->setPrice($itemPrice);
            $items[] = $item;
        }

        // Set merchant options
        $merchantOptions = new MerchantOptions();
        $merchantOptions
            ->setRedirectConfirmUrl(route('scalapay.success'))
            ->setRedirectCancelUrl(route('scalapay.cancel'));

        // set Order total amount object
        $totalAmount = new Money();
        $totalAmount->setAmount($this->formatCurrencyValue($cart->sub_total + $cart->tax_total + ($shippingRate) - $cart->discount_amount, 2));

        // set Tax total amount object
        $taxAmount = new Money();
        $taxAmount->setAmount($this->formatCurrencyValue((float) $cart->tax_total));

        // set Shipping total amount object
        $shippingAmount = new Money();
        $shippingAmount->setAmount($this->formatCurrencyValue((float) ($shippingRate)));

        // set Discount total object
        $discountAmount = new Money();
        $discountAmount->setAmount($this->formatCurrencyValue((float) $cart->discount_amount));
        $discount = new Discount();
        $discount
            ->setDisplayName('scalapay')
            ->setAmount($discountAmount);
        $discountList = array();
        $discountList[] = $discount;

        // set Frequency object
        $frequency = new Frequency();
        $frequency
            ->setFrequencyType('monthly')
            ->setNumber(1);

        // set Order Details object
        $orderDetails = new OrderDetails();
        $orderDetails->setConsumer($consumer)
            ->setBilling($billing)
            ->setShipping($shipping)
            ->setMerchant($merchantOptions)
            ->setItems($itemList)
            ->setTotalAmount($totalAmount)
            ->setShippingAmount($shippingAmount)
            ->setTaxAmount($taxAmount)
            ->setDiscounts($discountList)
            ->setMerchantReference($cart->id . '') // merchant reference is the order id in your platform
            ->setType('online')
            ->setProduct('pay-in-3')
            ->setFrequency($frequency);

        $createOrderResponse = $this->scalapayApi->createOrder($orderDetails);

        return redirect()->away($createOrderResponse->getCheckoutUrl());
    }

    /**
     * Place an order and redirect to the success page.
     */
    public function success()
    {
        try {
            // status=SUCCESS is the other parameter in the request
            $orderToken = request()->get('orderToken');
            $orderDetailsResponse = $this->scalapayApi->getOrderDetails($orderToken);

            if ($orderDetailsResponse['body']['status'] !== 'authorized') {
                throw new \Exception('Payment not authorized');
            }
            
            $captureResponse = $this->scalapayApi->capture($orderToken);
            
            if ($captureResponse['body']['status'] !== 'APPROVED') {
                throw new \Exception('Payment not approved');
            }

            Cart::collectTotals();

            $this->validateOrder();

            $order = $this->orderRepository->create(Cart::prepareDataForOrder());

            $this->orderRepository->update(['status' => Order::STATUS_COMPLETED], $order->id);

            if ($order->canInvoice()) {
                $this->invoiceRepository->create($this->prepareInvoiceData($order));
            }

            Cart::deActivateCart();

            session()->flash('order', $order);

            return redirect()->route('shop.checkout.onepage.success');
        } catch (\Exception $e) {
            session()->flash('error', trans('shop::app.common.error'));

            return redirect()->route('shop.checkout.cart.index');
        }
    }

    /**
     * Redirect to the cart page with error message.
     */
    public function failure(): RedirectResponse
    {
        session()->flash('error', 'Scalapay payment was either cancelled or the transaction failed.');

        return redirect()->route('shop.checkout.cart.index');
    }

    /**
     * Prepares order's invoice data for creation.
     */
    protected function prepareInvoiceData($order): array
    {
        $invoiceData = [
            'order_id' => $order->id,
            'invoice'  => ['items' => []],
        ];

        foreach ($order->items as $item) {
            $invoiceData['invoice']['items'][$item->id] = $item->qty_to_invoice;
        }

        return $invoiceData;
    }

    public function formatCurrencyValue($number): float
    {
        return round((float) $number, 2);
    }

    /**
     * Return convert multiple address lines into 2 address lines.
     *
     * @param  string  $address
     * @return array
     */
    protected function getAddressLines($address)
    {
        $address = explode(PHP_EOL, $address, 2);

        $addressLines = [current($address)];

        if (isset($address[1])) {
            $addressLines[] = str_replace(["\r\n", "\r", "\n"], ' ', last($address));
        } else {
            $addressLines[] = '';
        }

        return $addressLines;
    }

    /**
     * Validate order before creation.
     *
     * @return void|\Exception
     */
    protected function validateOrder()
    {
        $cart = Cart::getCart();

        $minimumOrderAmount = (float) core()->getConfigData('sales.order_settings.minimum_order.minimum_order_amount') ?: 0;

        if (! $cart->checkMinimumOrder()) {
            throw new \Exception(trans('shop::app.checkout.cart.minimum-order-message', ['amount' => core()->currency($minimumOrderAmount)]));
        }

        if (
            $cart->haveStockableItems()
            && ! $cart->shipping_address
        ) {
            throw new \Exception(trans('shop::app.checkout.cart.check-shipping-address'));
        }

        if (! $cart->billing_address) {
            throw new \Exception(trans('shop::app.checkout.cart.check-billing-address'));
        }

        if (
            $cart->haveStockableItems()
            && ! $cart->selected_shipping_rate
        ) {
            throw new \Exception(trans('shop::app.checkout.cart.specify-shipping-method'));
        }

        if (! $cart->payment) {
            throw new \Exception(trans('shop::app.checkout.cart.specify-payment-method'));
        }
    }
}
