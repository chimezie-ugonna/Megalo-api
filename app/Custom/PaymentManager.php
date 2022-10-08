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
        case "create_customer": {
            $this->createCustomer($data["name"], $data["phone"]);
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
        case "delete_customer": {
            $this->withdraw();
            break;
          }
      }
    } catch (CardException $e) {
      if ($e->getError()->payment_intent->charges->data[0]->outcome->type == 'blocked') {
        $message = "Card was blocked for suspected fraud.";
      } elseif ($e->getError()->code == 'expired_card') {
        $message = "Card has expired.";
      } elseif ($e->getError()->code == 'card_declined') {
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

  function createCustomer($name, $phone)
  {
    $this->stripe->customers->create([
      
    ]);
  }

  function deposit()
  {
  }

  function withdraw()
  {
  }

  function deleteCustomer()
  {
  }

  function getPaymentProcessingFee($amount_usd)
  {
    return ($amount_usd * 0.029) + 0.30;
  }

  function getMegaloFee($amount_usd)
  {
    return $amount_usd * 0.03;
  }

  function getReferralBonus()
  {
    return 5;
  }
}
