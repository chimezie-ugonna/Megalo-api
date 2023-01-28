<?php

namespace App\Custom;

use DateTime;
use Onfido\Api\DefaultApi;
use Onfido\Configuration;
use Onfido\Model\ApplicantRequest;
use Onfido\Model\SdkTokenRequest;

class IdentityVerifier
{
    private $token;
    private $api_instance;

    function __construct()
    {
        $this->token = getenv("ONFIDO_TOKEN");
        $config = Configuration::getDefaultConfiguration();
        $config->setApiKey("Authorization", "token=" . $this->token);
        $config->setApiKeyPrefix("Authorization", "Token");
        $this->api_instance = new DefaultApi(null, $config);
    }

    function createApplicant($first_name, $last_name, $dob)
    {
        try {
            $applicant_details = new ApplicantRequest();

            $applicant_details->setFirstName($first_name);
            $applicant_details->setLastName($last_name);
            $date_obj = DateTime::createFromFormat("d/m/Y", $dob);
            $new_dob = $date_obj->format("Y-m-d");
            $applicant_details->setDob($new_dob);

            $applicant_result = $this->api_instance->createApplicant($applicant_details);
            return $applicant_result;
        } catch (\Exception) {
            return false;
        }
    }

    function generateSdkToken($applicant_id, $app_id_or_app_bundle_id)
    {
        try {
            $sdk_token_request = new SdkTokenRequest();

            $sdk_token_request->setApplicantId($applicant_id);
            $sdk_token_request->setApplicationID($app_id_or_app_bundle_id);

            return $this->api_instance->generateSdkToken($sdk_token_request);
        } catch (\Exception) {
            return false;
        }
    }
}
