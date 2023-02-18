<?php

namespace App\Custom;

class Localization
{
    private $english;
    private $german;
    private $language;
    function __construct($ip_address, $data)
    {
        $referral_payment_usd = 0;
        if (array_key_exists("referral_payment_usd", $data)) {
            $referral_payment_usd = $data["referral_payment_usd"];
        }
        $user_percentage_of_property_monthly_earning = 0;
        if (array_key_exists("user_percentage_of_property_monthly_earning", $data)) {
            $user_percentage_of_property_monthly_earning = $data["user_percentage_of_property_monthly_earning"];
        }
        $user_percentage_of_property_value = 0;
        if (array_key_exists("user_percentage_of_property_value", $data)) {
            $user_percentage_of_property_value = $data["user_percentage_of_property_value"];
        }
        $amount = 0;
        if (array_key_exists("amount", $data)) {
            $amount = $data["amount"];
        }
        $this->english = [
            "referral_bonus_received_title" => "Referral bonus received!!!",
            "referral_bonus_received_body_referrer" => "You have just received $" . $referral_payment_usd . " in your balance because someone you referred with your referral code has invested $100 or more on Megalo. Keep referring people to earn more!",
            "referral_bonus_received_body_referree" => "You have just received $" . $referral_payment_usd . " in your balance because you joined Megalo with someone's referral code and have invested $100 or more on Megalo. You can earn more if you refer someone too.",
            "new_property_available_title" => "New property available!!!",
            "new_property_available_body" => "We just listed a new property, be among the first to invest in it and reap the benefits.",
            "property_dividend_payment_title" => "Property dividend payment!!!",
            "property_dividend_payment_body" => "You just received $" . $user_percentage_of_property_monthly_earning . " in your balance as dividend from a property you invested in.",
            "property_value_increase_title" => "Property value increase!!!",
            "property_value_increase_body" => "A property that you invested in has increased in value.",
            "property_earnings_increase_title" => "Property earnings increase!!!",
            "property_earnings_increase_body" => "A property that you invested in has increased its earnings.",
            "property_sale_payment_title" => "Property sale payment!!!",
            "property_sale_payment_body" => "You just received $" . $user_percentage_of_property_value . " in your balance as payment from the sale of a property you invested in.",
            "insufficient_fund_email_subject" => "Withdrawal failure caused by insufficient fund",
            "insufficient_fund_email_title" => "Urgent Action Required",
            "insufficient_fund_email_body" => "A payment failure has just occurred while a user was trying to withdraw a sum of $" . $amount . " from their Megalo balance. This failure occurred because there is not enough money in Megalo's stripe account balance for that transaction. A top up of Megalo's stripe account balance with enough money to cover the user's withdrawal amount ($" . $amount . ") is required as soon as possible so as to not degrade the user's experience on Megalo.",
            "insufficient_fund_email_footer" => "You received this email because you are a Megalo administrator.",
            "verification_email_subject" => "Megalo Verification Code",
            "verification_email_title" => "Verify Your Email Addres",
            "verification_email_body" => "Your Megalo verification code is: ",
            "verification_email_footer" => "You received this email because we received an email verification request for your Megalo account. If you didn't request for email verification you can safely delete this email.",
            "identity_verification_success_title" => "Identity verification successful",
            "identity_verification_success_body" => "Your identity was successfully verified.",
            "identity_verification_success_under_age" => "Your identity was successfully verified but you need to be 18 years or older to use Megalo.",
            "identity_verification_failed_title" => "Identity verification failed",
            "identity_verification_failed_body" => "Your identity was not verified successfully. This could mainly be either because your selfie face was not successfully matched with the face on your document or because the document you provided could not be validated.",
            "identity_verification_failed_data_mismatch" => "Your identity was not verified successfully because the data on your profile does not match the data on your document.",
            "test" => "This is a test."
        ];

        $this->german = [
            "referral_bonus_received_title" => "Empfehlungsbonus erhalten!!!",
            "referral_bonus_received_body_referrer" => "Sie haben gerade $" . $referral_payment_usd . " auf Ihrem Guthaben erhalten, weil jemand, den Sie mit Ihrem Empfehlungscode geworben haben, 100 $ oder mehr bei Megalo investiert hat. Empfehlen Sie weiter Leute, um mehr zu verdienen!",
            "referral_bonus_received_body_referree" => "Sie haben gerade $" . $referral_payment_usd . " auf Ihrem Guthaben erhalten, weil Sie Megalo mit dem Empfehlungscode einer anderen Person beigetreten sind und 100 $ oder mehr in Megalo investiert haben. Sie können mehr verdienen, wenn Sie auch jemanden empfehlen.",
            "new_property_available_title" => "Neue Immobilie verfügbar!!!",
            "new_property_available_body" => "Wir haben gerade eine neue Immobilie gelistet, gehören Sie zu den Ersten, die darin investieren, und profitieren Sie von den Vorteilen.",
            "property_dividend_payment_title" => "Auszahlung der Vermögensdividende!!!",
            "property_dividend_payment_body" => "Sie haben gerade $" . $user_percentage_of_property_monthly_earning . " in Ihrem Guthaben als Dividende von einer Immobilie erhalten, in die Sie investiert haben.",
            "property_value_increase_title" => "Wertsteigerung der Immobilie!!!",
            "property_value_increase_body" => "Eine Immobilie, in die Sie investiert haben, hat an Wert gewonnen.",
            "property_earnings_increase_title" => "Grundstückserträge steigen!!!",
            "property_earnings_increase_body" => "Eine Immobilie, in die Sie investiert haben, hat ihre Erträge gesteigert.",
            "property_sale_payment_title" => "Zahlung beim Immobilienverkauf!!!",
            "property_sale_payment_body" => "Sie haben gerade $" . $user_percentage_of_property_value . " als Zahlung für den Verkauf einer Immobilie erhalten, in die Sie investiert haben.",
            "insufficient_fund_email_subject" => "Auszahlungsfehler aufgrund unzureichender Deckung",
            "insufficient_fund_email_title" => "Dringende Maßnahme erforderlich",
            "insufficient_fund_email_body" => "Ein Zahlungsfehler ist gerade aufgetreten, während ein Benutzer versucht hat, eine Summe von $" . $amount . " von seinem Megalo-Guthaben abzuheben. Dieser Fehler ist aufgetreten, weil auf dem Stripe-Konto von Megalo nicht genügend Geld für diese Transaktion vorhanden ist. Eine Aufladung des Stripe-Kontostands von Megalo mit genügend Geld zur Deckung des Auszahlungsbetrags des Benutzers ($" . $amount . ") ist so schnell wie möglich erforderlich, um die Erfahrung des Benutzers auf Megalo nicht zu beeinträchtigen.",
            "insufficient_fund_email_footer" => "Sie haben diese E-Mail erhalten, weil Sie ein Megalo-Administrator sind.",
            "verification_email_subject" => "Megalo-Bestätigungscode",
            "verification_email_title" => "Bestätige deine Email-Adresse",
            "verification_email_body" => "Ihr Megalo-Bestätigungscode lautet: ",
            "verification_email_footer" => "Sie haben diese E-Mail erhalten, weil wir eine E-Mail-Verifizierungsanfrage für Ihr Megalo-Konto erhalten haben. Wenn Sie keine E-Mail-Bestätigung angefordert haben, können Sie diese E-Mail sicher löschen.",
            "identity_verification_success_title" => "Identitätsprüfung erfolgreich",
            "identity_verification_success_body" => "Ihre Identität wurde erfolgreich verifiziert.",
            "identity_verification_success_under_age" => "Ihre Identität wurde erfolgreich verifiziert, aber Sie müssen mindestens 18 Jahre alt sein, um Megalo nutzen zu können.",
            "identity_verification_failed_title" => "Identitätsüberprüfung fehlgeschlagen",
            "identity_verification_failed_body" => "Ihre Identität wurde nicht erfolgreich verifiziert. Dies kann hauptsächlich daran liegen, dass Ihr Selfie-Gesicht nicht erfolgreich mit dem Gesicht auf Ihrem Dokument abgeglichen wurde oder dass das von Ihnen bereitgestellte Dokument nicht validiert werden konnte.",
            "identity_verification_failed_data_mismatch" => "Ihre Identität wurde nicht erfolgreich verifiziert, da die Daten in Ihrem Profil nicht mit den Daten in Ihrem Dokument übereinstimmen.",
            "test" => "Das ist ein Test."
        ];

        $ip_address = $ip_address;
        if ($ip_address != "") {
            $ip_address_manager = new IpAddressManager();
            $country = $ip_address_manager->getIpAddressDetails($ip_address, "Country");
            if (isset($country)) {
                if ($country == "Germany") {
                    $this->language = "German";
                } else {
                    $this->language = "English";
                }
            } else {
                $this->language = "English";
            }
        } else {
            $this->language = "English";
        }
    }

    function getText($key)
    {
        if (array_key_exists($key, $this->english)) {
            if ($this->language == "English") {
                return $this->english[$key];
            } else if ($this->language == "German") {
                return $this->german[$key];
            }
        } else {
            return false;
        }
    }
}
