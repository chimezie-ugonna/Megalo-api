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
            return $this->createAccount();
            break;
          }
        case "create_customer": {
            return $this->createCustomer();
            break;
          }
        case "create_token": {
            return $this->createToken($data["data"]);
            break;
          }
        case "add_account_payment_method": {
            return $this->addAccountPaymentMethod($data["account_id"], $data["data"]);
            break;
          }
        case "add_customer_payment_method": {
            return $this->addCustomerPaymentMethod($data["customer_id"], $data["data"]);
            break;
          }
        case "verify_customer_bank_account": {
            return $this->verifyCustomerBankAccount($data["customer_id"], $data["data"]);
            break;
          }
        case "retrieve_account_payment_method": {
            return $this->retrieveAccountPaymentMethod($data["account_id"], $data["data"]);
            break;
          }
        case "retrieve_customer_payment_method": {
            return $this->retrieveCustomerPaymentMethod($data["customer_id"], $data["data"]);
            break;
          }
        case "list_all_account_payment_method": {
            return $this->listAllAccountPaymentMethod($data["account_id"], $data["data"]);
            break;
          }
        case "list_all_customer_payment_method": {
            return $this->listAllCustomerPaymentMethod($data["customer_id"], $data["data"]);
            break;
          }
        case "delete_account_payment_method": {
            return $this->deleteAccountPaymentMethod($data["account_id"], $data["data"]);
            break;
          }
        case "delete_customer_payment_method": {
            return $this->deleteCustomerPaymentMethod($data["customer_id"], $data["data"]);
            break;
          }
        case "deposit": {
            return $this->deposit($data["customer_id"], $data["data"]);
            break;
          }
        case "retrieve_balance": {
            return $this->retrieveBalance();
            break;
          }
        case "withdraw": {
            return $this->withdraw($data["account_id"], $data["data"]);
            break;
          }
        case "delete_account": {
            return $this->deleteAccount($data["account_id"]);
            break;
          }
        case "delete_customer": {
            return $this->deleteCustomer($data["customer_id"]);
            break;
          }
      }
    } catch (CardException $e) {
      return response()->json([
        "status" => false,
        "message" => $e->getError()->message
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

  function createToken($data)
  {
    if ($data["type"] == "card") {
      return $this->stripe->tokens->create([
        $data["type"] => [
          'number' => $data["number"],
          'exp_month' => $data["exp_month"],
          'exp_year' => $data["exp_year"],
          'cvc' => $data["cvc"],
        ],
      ]);
    } else if ($data["type"] == "bank_account") {
      return $this->stripe->tokens->create([
        $data["type"] => [
          'country' => $data["country"],
          'currency' => $data["currency"],
          'account_number' => $data["account_number"],
        ],
      ]);
    }
  }

  function createAccount()
  {
    return $this->stripe->accounts->create([
      "type" => "custom",
      "business_type" => "individual",
      "capabilities" => [
        "card_payments" => ["requested" => true],
        "transfers" => ["requested" => true],
      ],
    ]);
  }

  function addAccountPaymentMethod($account_id, $data)
  {
    return $this->stripe->accounts->createExternalAccount(
      $account_id,
      ['external_account' => $data["token"]]
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
        ['object' => $data["type"]]
      );
    } else {
      return $this->stripe->accounts->allExternalAccounts(
        $account_id,
        ['object' => $data["type"], 'limit' => $data["limit"]]
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
      'amount' => ($data["amount"] / 100),
      'currency' => $data["currency"],
      'destination' => $account_id
    ]);
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
    return $this->stripe->customers->create([]);
  }

  function addCustomerPaymentMethod($customer_id, $data)
  {
    return $this->stripe->customers->createSource(
      $customer_id,
      ['source' => $data["token"]]
    );
  }

  function verifyCustomerBankAccount($customer_id, $data)
  {
    return $this->stripe->customers->verifySource(
      $customer_id,
      $data["id"],
      ['amounts' => [32, 45]]
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
        ['object' => $data["type"]]
      );
    } else {
      return $this->stripe->customers->allSources(
        $customer_id,
        ['object' => $data["type"], 'limit' => $data["limit"]]
      );
    }
  }

  function deposit($customer_id, $data)
  {
    return $this->stripe->charges->create([
      'amount' => ($data["amount"] / 100),
      'currency' => $data["currency"],
      'customer' => $customer_id
    ]);
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
