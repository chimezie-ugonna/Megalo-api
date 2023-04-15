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
      switch ($data["type"]) {
        case "create_account": {
            $response = $this->createAccount($data["data"]);
            break;
          }
        case "create_customer": {
            $response = $this->createCustomer($data["data"]);
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
      return $response;
    } catch (Exception $e) {
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
          "message" => "A payment error occurred with the provided parameters."
        ], 500)->throwResponse();
      } else if ($e instanceof AuthenticationException) {
        return response()->json([
          "status" => false,
          "message" => "A payment error occurred with authentication."
        ], 500)->throwResponse();
      } else if ($e instanceof ApiConnectionException) {
        return response()->json([
          "status" => false,
          "message" => "A payment error occurred with network communication."
        ], 500)->throwResponse();
      } else if ($e instanceof ApiErrorException) {
        return response()->json([
          "status" => false,
          "message" => "A payment api error occurred."
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
      $parameters = [
        "number" => $data["number"],
        "exp_month" => $data["exp_month"],
        "exp_year" => $data["exp_year"],
        "cvc" => $data["cvc"]
      ];
      if ($data["action"] == "withdrawal") {
        $parameters["currency"] = $data["currency"];
      }
      return $this->stripe->tokens->create([
        $data["type"] => $parameters,
      ]);
    } else if ($data["type"] == "bank_account") {
      return $this->stripe->tokens->create([
        $data["type"] => [
          "country" => $data["country"],
          "currency" => $data["currency"],
          "account_holder_name" => $data["account_holder_name"],
          "account_holder_type" => $data["account_holder_type"],
          "account_number" => $data["account_number"],
          "routing_number" => $data["routing_number"]
        ],
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
      "individual" => [
        "first_name" => $data["first_name"],
        "last_name" => $data["last_name"],
        "gender" => $data["gender"],
        "dob" => ["day" => $data["day_of_birth"], "month" => $data["month_of_birth"], "year" => $data["year_of_birth"]]
      ],
      "tos_acceptance" => [
        "service_agreement" => "recipient",
        "date" => $data["time_stamp"],
        "ip" => $data["ip_address"]
      ]
    ]);
  }

  function addAccountPaymentMethod($account_id, $data)
  {
    return $this->stripe->accounts->createExternalAccount(
      $account_id,
      ["external_account" => $data["token"]]
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
    ]);
  }

  function updateDefaultAccountPaymentMethod($account_id, $data)
  {
    return $this->stripe->accounts->updateExternalAccount(
      $account_id,
      $data["id"],
      ["default_for_currency" => true]
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

  function createCustomer($data)
  {
    return $this->stripe->customers->create(["name" => $data["full_name"]]);
  }

  function addCustomerPaymentMethod($customer_id, $data)
  {
    return $this->stripe->customers->createSource(
      $customer_id,
      ["source" => $data["token"]]
    );
  }

  function verifyCustomerBankAccount($customer_id, $data)
  {
    return $this->stripe->customers->verifySource(
      $customer_id,
      $data["id"],
      ["amounts" => [32, 45]]
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
    ]);
  }

  function updateDefaultCustomerPaymentMethod($customer_id, $data)
  {
    return $this->stripe->customers->update(
      $customer_id,
      ["default_source" => $data["id"]]
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
