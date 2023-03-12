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
use Stripe\Stripe;

class PaymentManager
{

  private $stripe;

  function __construct()
  {
    $this->stripe = new StripeClient(getenv("STRIPE_API_KEY"));
    Stripe::setMaxNetworkRetries(2);
  }

  function manage($data = [])
  {
    try {
      session_start();
      if (!isset($_SESSION["idempotency_key"]) || !isset($_SESSION["data"])) {
        $_SESSION["idempotency_key"] = uniqid(rand(), true);
        $_SESSION["data"] = encrypt($data);
      } else {
        if (decrypt($_SESSION["data"]) != $data) {
          $_SESSION["idempotency_key"] = uniqid(rand(), true);
          $_SESSION["data"] = encrypt($data);
        }
      }
      return response()->json([
        "status" => false,
        "encrypted_data" => $_SESSION["data"],
        "decrypted_data" => decrypt($_SESSION["data"])
      ], 400)->throwResponse();

      switch ($data["type"]) {
        case "create_account": {
            $response = $this->createAccount($data["data"]);
            break;
          }
        case "create_customer": {
            $response = $this->createCustomer();
            break;
          }
        case "create_token": {
            $response = $this->createToken($data["data"]);
            break;
          }
        case "add_account_payment_method": {
            $response = $this->addAccountPaymentMethod($data["account_id"], $data["data"]);
            break;
          }
        case "add_customer_payment_method": {
            $response = $this->addCustomerPaymentMethod($data["customer_id"], $data["data"]);
            break;
          }
        case "verify_customer_bank_account": {
            $response = $this->verifyCustomerBankAccount($data["customer_id"], $data["data"]);
            break;
          }
        case "retrieve_account_payment_method": {
            $response = $this->retrieveAccountPaymentMethod($data["account_id"], $data["data"]);
            break;
          }
        case "retrieve_customer_payment_method": {
            $response = $this->retrieveCustomerPaymentMethod($data["customer_id"], $data["data"]);
            break;
          }
        case "list_all_account_payment_method": {
            $response = $this->listAllAccountPaymentMethod($data["account_id"], $data["data"]);
            break;
          }
        case "list_all_customer_payment_method": {
            $response = $this->listAllCustomerPaymentMethod($data["customer_id"], $data["data"]);
            break;
          }
        case "update_default_account_payment_method": {
            $response = $this->updateDefaultAccountPaymentMethod($data["account_id"], $data["data"]);
            break;
          }
        case "update_default_customer_payment_method": {
            $response = $this->updateDefaultCustomerPaymentMethod($data["customer_id"], $data["data"]);
            break;
          }
        case "delete_account_payment_method": {
            $response = $this->deleteAccountPaymentMethod($data["account_id"], $data["data"]);
            break;
          }
        case "delete_customer_payment_method": {
            $response = $this->deleteCustomerPaymentMethod($data["customer_id"], $data["data"]);
            break;
          }
        case "deposit": {
            $response = $this->deposit($data["customer_id"], $data["data"]);
            break;
          }
        case "retrieve_balance": {
            $response = $this->retrieveBalance();
            break;
          }
        case "withdraw": {
            $response = $this->withdraw($data["account_id"], $data["data"]);
            break;
          }
        case "delete_account": {
            $response = $this->deleteAccount($data["account_id"]);
            break;
          }
        case "delete_customer": {
            $response = $this->deleteCustomer($data["customer_id"]);
            break;
          }
      }
      session_unset();
      session_destroy();
      return $response;
    } catch (Exception $e) {
      session_unset();
      session_destroy();
      if ($e instanceof CardException) {
        return response()->json([
          "status" => false,
          "message" => $e->getError()->message
        ], 400)->throwResponse();
      } else if ($e instanceof RateLimitException) {
        return response()->json([
          "status" => false,
          "message" => "Too many requests made too quickly."
        ], 429)->throwResponse();
      } else if ($e instanceof InvalidRequestException) {
        return response()->json([
          "status" => false,
          "message" => "A payment error occurred with the provided parameters from our end."
        ], 500)->throwResponse();
      } else if ($e instanceof AuthenticationException) {
        return response()->json([
          "status" => false,
          "message" => "A payment error occurred with authentication from our end."
        ], 500)->throwResponse();
      } else if ($e instanceof ApiConnectionException) {
        return response()->json([
          "status" => false,
          "message" => "A payment error occurred with network communication from our end."
        ], 500)->throwResponse();
      } else if ($e instanceof ApiErrorException) {
        return response()->json([
          "status" => false,
          "message" => "An unexpected payment error occurred from our end."
        ], 500)->throwResponse();
      } else if ($e instanceof IdempotencyException) {
        return response()->json([
          "status" => false,
          "message" => "A payment error occurred from our end. An idempotency key was used for something unexpected, like replaying a request but passing different parameters."
        ], 500)->throwResponse();
      } else {
        return response()->json([
          "status" => false,
          "message" => "An unexpected payment error occurred from our end."
        ], 500)->throwResponse();
      }
    }
  }

  function createToken($data)
  {
    if ($data["type"] == "card") {
      return $this->stripe->tokens->create([
        $data["type"] => [
          "number" => $data["number"],
          "exp_month" => $data["exp_month"],
          "exp_year" => $data["exp_year"],
          "cvc" => $data["cvc"],
        ],
      ], [
        "idempotency_key" => $_SESSION["idempotency_key"]
      ]);
    } else if ($data["type"] == "bank_account") {
      return $this->stripe->tokens->create([
        $data["type"] => [
          "country" => $data["country"],
          "currency" => $data["currency"],
          "account_number" => $data["account_number"],
        ],
      ], [
        "idempotency_key" => $_SESSION["idempotency_key"]
      ]);
    }
  }

  function createAccount($data)
  {
    return $this->stripe->accounts->create([
      "type" => "custom",
      "business_type" => "individual",
      "capabilities" => [
        "transfers" => ["requested" => true]
      ],
      "tos_acceptance" => [
        "date" => $data["time_stamp"],
        "ip" => $data["ip_address"]
      ]
    ], [
      "idempotency_key" => $_SESSION["idempotency_key"]
    ]);
  }

  function addAccountPaymentMethod($account_id, $data)
  {
    return $this->stripe->accounts->createExternalAccount(
      $account_id,
      ["external_account" => $data["token"]],
      [
        "idempotency_key" => $_SESSION["idempotency_key"]
      ]
    );
  }

  function retrieveAccountPaymentMethod($account_id, $data)
  {
    return $this->stripe->accounts->retrieveExternalAccount(
      $account_id,
      $data["id"],
      []
    );
  }

  function listAllAccountPaymentMethod($account_id, $data)
  {
    if (!array_key_exists("limit", $data)) {
      return $this->stripe->accounts->allExternalAccounts(
        $account_id,
        ["object" => $data["type"]]
      );
    } else {
      return $this->stripe->accounts->allExternalAccounts(
        $account_id,
        ["object" => $data["type"], "limit" => $data["limit"]]
      );
    }
  }

  function retrieveBalance()
  {
    return $this->stripe->balance->retrieve();
  }

  function withdraw($account_id, $data)
  {
    return $this->stripe->transfers->create([
      "amount" => ($data["amount"] / 100),
      "currency" => $data["currency"],
      "destination" => $account_id
    ], [
      "idempotency_key" => $_SESSION["idempotency_key"]
    ]);
  }

  function updateDefaultAccountPaymentMethod($account_id, $data)
  {
    return $this->stripe->accounts->updateExternalAccount(
      $account_id,
      $data["id"],
      ["default_for_currency" => true],
      [
        "idempotency_key" => $_SESSION["idempotency_key"]
      ]
    );
  }

  function deleteAccountPaymentMethod($account_id, $data)
  {
    return $this->stripe->accounts->deleteExternalAccount(
      $account_id,
      $data["id"],
      []
    );
  }

  function deleteAccount($account_id)
  {
    return $this->stripe->accounts->delete(
      $account_id,
      []
    );
  }

  function createCustomer()
  {
    return $this->stripe->customers->create([], [
      "idempotency_key" => $_SESSION["idempotency_key"]
    ]);
  }

  function addCustomerPaymentMethod($customer_id, $data)
  {
    return $this->stripe->customers->createSource(
      $customer_id,
      ["source" => $data["token"]],
      [
        "idempotency_key" => $_SESSION["idempotency_key"]
      ]
    );
  }

  function verifyCustomerBankAccount($customer_id, $data)
  {
    return $this->stripe->customers->verifySource(
      $customer_id,
      $data["id"],
      ["amounts" => [32, 45]],
      [
        "idempotency_key" => $_SESSION["idempotency_key"]
      ]
    );
  }

  function retrieveCustomerPaymentMethod($customer_id, $data)
  {
    return $this->stripe->customers->retrieveSource(
      $customer_id,
      $data["id"],
      []
    );
  }

  function listAllCustomerPaymentMethod($customer_id, $data)
  {
    if (!array_key_exists("limit", $data)) {
      return $this->stripe->customers->allSources(
        $customer_id,
        ["object" => $data["type"]]
      );
    } else {
      return $this->stripe->customers->allSources(
        $customer_id,
        ["object" => $data["type"], "limit" => $data["limit"]]
      );
    }
  }

  function deposit($customer_id, $data)
  {
    return $this->stripe->charges->create([
      "amount" => ($data["amount"] / 100),
      "currency" => $data["currency"],
      "customer" => $customer_id
    ], [
      "idempotency_key" => $_SESSION["idempotency_key"]
    ]);
  }

  function updateDefaultCustomerPaymentMethod($customer_id, $data)
  {
    return $this->stripe->customers->update(
      $customer_id,
      ["default_source" => $data["id"]],
      [
        "idempotency_key" => $_SESSION["idempotency_key"]
      ]
    );
  }

  function deleteCustomerPaymentMethod($customer_id, $data)
  {
    return $this->stripe->customers->deleteSource(
      $customer_id,
      $data["id"],
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

  function getPaymentProcessingFee($amount_usd, $type)
  {
    if ($type == "withdrawal") {
      return ($amount_usd * 0.029) + 0.10;
    } else {
      return ($amount_usd * 0.029) + 0.30;
    }
  }

  function getEarlyLiquidationFee($amount_usd)
  {
    return $amount_usd * 0.03;
  }

  function getReferralBonus()
  {
    return 5;
  }
}
