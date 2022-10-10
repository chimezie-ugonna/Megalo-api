<?php

namespace App\Custom;

use Exception;
use Stripe\StripeClient;
use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\AuthenticationException;
use Stripe\Exception\CardException;
use Stripe\Exception\IdempotencyException;
use Stripe\Exception\InvalidRequestException;
use Stripe\Exception\RateLimitException;

class PaymentManager
{

  private $stripe;

  function __construct()
  {
    $this->stripe = new StripeClient(getenv("STRIPE_API_KEY"));
  }

  function manage($data)
  {
    try {
      switch ($data["type"]) {
        case "create_account": {
            $this->createAccount();
            break;
          }
        case "create_customer": {
            $this->createCustomer();
            break;
          }
        case "deposit": {
            $this->deposit();
            break;
          }
        case "withdraw": {
            $this->withdraw();
            break;
          }
        case "delete_account": {
            $this->deleteAccount($data["account_id"]);
            break;
          }
        case "delete_customer": {
            $this->deleteCustomer($data["customer_id"]);
            break;
          }
      }
    } catch (CardException $e) {
      if ($e->getError()->payment_intent->charges->data[0]->outcome->type == "blocked") {
        $message = "Card was blocked for suspected fraud.";
      } elseif ($e->getError()->code == "expired_card") {
        $message = "Card has expired.";
      } elseif ($e->getError()->code == "card_declined") {
        $message = "Card was declined by the issuer.";
      } else {
        $message = "We encountered an error with your card.";
      }
      return response()->json([
        "status" => false,
        "message" => $message
      ], 400)->throwResponse();
    } catch (RateLimitException $e) {
      return response()->json([
        "status" => false,
        "message" => "Too many requests made too quickly."
      ], 429)->throwResponse();
    } catch (InvalidRequestException $e) {
      return response()->json([
        "status" => false,
        "message" => "A payment error occurred with the provided parameters from our end."
      ], 500)->throwResponse();
    } catch (AuthenticationException $e) {
      return response()->json([
        "status" => false,
        "message" => "A payment error occurred with authentication from our end."
      ], 500)->throwResponse();
    } catch (ApiConnectionException $e) {
      return response()->json([
        "status" => false,
        "message" => "A payment error occurred with network communication from our end."
      ], 500)->throwResponse();
    } catch (ApiErrorException $e) {
      return response()->json([
        "status" => false,
        "message" => "An unexpected payment error occurred from our end."
      ], 500)->throwResponse();
    } catch (IdempotencyException $e) {
      return response()->json([
        "status" => false,
        "message" => "A payment error occurred from our end. An idempotency key was used for something unexpected, like replaying a request but passing different parameters."
      ], 500)->throwResponse();
    } catch (Exception $e) {
      return response()->json([
        "status" => false,
        "message" => "An unexpected payment error occurred from our end."
      ], 500)->throwResponse();
    }
  }

  function createAccount()
  {
    return $this->stripe->accounts->create([
      "type" => "express",
      "business_type" => "individual",
      "capabilities" => [
        "card_payments" => ["requested" => true],
        "transfers" => ["requested" => true],
      ],
    ]);
  }

  function createCustomer()
  {
    return $this->stripe->customers->create();
  }

  function deposit()
  {
  }

  function withdraw()
  {
  }

  function deleteAccount($account_id)
  {
    return $this->stripe->accounts->delete(
      $account_id,
      []
    );
  }

  function deleteCustomer($customer_id)
  {
    return $this->stripe->customers->delete(
      $customer_id,
      []
    );
  }

  function getPaymentProcessingFee($amount_usd)
  {
    return ($amount_usd * 0.029) + 0.30;
  }

  function getEarlyLiquidationFee($amount_usd)
  {
    return $amount_usd * 0.03;
  }

  function getInvestmentFee($amount_usd)
  {
    return $amount_usd * 0.01;
  }

  function getReferralBonus()
  {
    return 5;
  }
}
