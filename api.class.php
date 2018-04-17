<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';
// OAuth2 client from here: https://github.com/thephpleague/oauth2-client
require_once __DIR__ . '/OAuth2/vendor/autoload.php';

class centralAPI
{
    private $accessTokenUrl = UNISYN_API_SERVER_URL . '/token';
    private $clientKey = '';
    private $clientSecret = '';
    private $grantType = 'client_credentials';

    public function __construct() {
        $this->clientKey = UNISYNAPIKEY;
        $this->clientSecret = UNISYNAPISECRET;

        if (!$this->clientKey) {
            echo '{"error": "No API key supplied in config."}';
            throw new Exception('No API key supplied in config.');
        }
        else if (!$this->clientSecret) {
            echo '{"error": "No API secret supplied in config."}';
            throw new Exception('No API secret supplied in config.');
        }
    }

    /**
     * A GET Request to the API.
     *
     * @param array $request
     *     'endpoint'
     *     'payload'
     *
     * @return mixed
     *     'error'
     *     'response'
     */
    public function getRequest($request) {
        if ( empty($request['endpoint']) )  {
            return '{"error": "No endpoint specified"}';
        }

        $formattedGetParams = '';
        if (!empty($request['payload'])) {
            foreach ($request['payload'] as $key => $value) {
                if ($value === null) {
                    // dont add the key to the url since the value is null
                }
                else {
                    $formattedValue = $value;
                    // json encode payload array values since they go in the url
                    if (is_array($value)) {
                        $formattedValue = json_encode($value);
                    }
                    $formattedGetParams .= $this->trailingslashit(rawurlencode($key)) . $this->trailingslashit(rawurlencode($formattedValue));
                }
            }
        }

        if ( count(explode('/',$request['endpoint'])) === 1 ) {
            // no verb provided so fill in a blank for them
            $request['endpoint'] = $this->trailingslashit($request['endpoint']) . 'null';
        }

        $endpointFormatted = $this->trailingslashit($request['endpoint']) . $formattedGetParams;
//         var_dump($endpointFormatted);

        $APIResponse = $this->doCall($endpointFormatted);
//         var_dump($APIResponse);
        $APIResponse = json_decode($APIResponse, true);

        return $APIResponse;
    }


    /**
     * A POST Request to the API.
     *
     * @param array $request
     *     'endpoint'
     *     'payload'
     *
     * @return mixed
     *     'error'
     *     'response'
     */
    public function postRequest($request) {
        $APIResponse = $this->doCall($request['endpoint'], 'POST', $request['payload']);
        // var_dump($APIResponse);
        $APIResponse = json_decode($APIResponse, true);

        return $APIResponse;
    }


    /**
     * A POST Request to the API.
     *
     * @param array $request
     *     'endpoint'
     *     'payload'
     *
     * @return mixed
     *     'error'
     *     'response'
     */
    public function deleteRequest($request) {
        if ( empty($request['endpoint']) )  {
            return '{"error": "No endpoint specified"}';
        }

        $formattedGetParams = '';
        if (!empty($request['payload'])) {
            foreach ($request['payload'] as $key => $value) {
                $formattedGetParams .= $this->trailingslashit(rawurlencode($key)) . $this->trailingslashit(rawurlencode($value));
            }
        }

        if ( count(explode('/',$request['endpoint'])) === 1 ) {
            // no verb provided so fill in a blank for them
            $request['endpoint'] = $this->trailingslashit($request['endpoint']) . 'null';
        }

        $endpointFormatted = $this->trailingslashit($request['endpoint']) . $formattedGetParams;
//         var_dump($endpointFormatted);

        $APIResponse = $this->doCall($endpointFormatted, 'DELETE');
//         var_dump($APIResponse);
        $APIResponse = json_decode($APIResponse, true);

        return $APIResponse;
    }

    public function doCall($endpoint, $method = 'GET', $payload = array()) {
        if (!$endpoint) {
            throw new Exception('No API endpoint supplied.');
        }
        $endpoint = $this->trailingslashit(UNISYN_API_SERVER_URL) . $endpoint;
//         var_dump($endpoint);

        $provider = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId'                => $this->clientKey,
            'clientSecret'            => $this->clientSecret,
            'redirectUri'             => '',
            'urlAuthorize'            => '',
            'urlAccessToken'          => $this->accessTokenUrl,
            'urlResourceOwnerDetails' => $endpoint
        ]);

        $rightNow = time();

        if (!isset($_SESSION['unisyn_api_access_token_details']['expires']) || $_SESSION['unisyn_api_access_token_details']['expires'] <= $rightNow) {
            try {
                // Try to get an access token using the authorization code grant.
                $accessToken = $provider->getAccessToken( $this->grantType );

                // We have an access token, which we may use in authenticated
                // requests against the service provider's API.
                $_SESSION['unisyn_api_access_token_details'] = array(
                    'access_token' => $accessToken->getToken(),
                    'expires' => $accessToken->getExpires()
                );

            } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
                // Failed to get the access token or user details.
                throw new Exception($e->getMessage());
            }
        }
        else {
            // token is still valid so proceed
        }

        if ($_SESSION['unisyn_api_access_token_details']['access_token']) {
            $cURL = curl_init();

            $header = array();
            $header[] = 'Authorization: Bearer ' . $rightNow . $_SESSION['unisyn_api_access_token_details']['access_token'];
            $header[] = 'UserAPIKey: ' . $_SESSION['userDetails']['APIKey'];

            if ( $method === 'POST' && !empty($payload) ) {
                curl_setopt($cURL, CURLOPT_POST, true);
                curl_setopt($cURL, CURLOPT_POSTFIELDS, http_build_query($payload) );
            }
            if ( $method === 'DELETE') {
                $header[] = 'X-HTTP-Method-Override: DELETE';
                curl_setopt($cURL, CURLOPT_CUSTOMREQUEST, "DELETE");
            }

            // set header
            curl_setopt($cURL, CURLOPT_HTTPHEADER,$header);
            // set url
            curl_setopt($cURL, CURLOPT_URL, $endpoint);
            curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);

            $APIResponse = curl_exec($cURL);
            $APIResponseCode = curl_getinfo($cURL, CURLINFO_HTTP_CODE);
            header("HTTP/1.1 " . $APIResponseCode . " " . $this->_requestStatus($APIResponseCode));

            // close curl resource, and free up system resources
            curl_close($cURL);

            return $APIResponse;
        }
        else {
            throw new Exception('Failed to get new token.');
        }
    }

    private function _requestStatus($code) {
        $status = array(
            200 => 'OK',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
        );
        return ($status[$code])?$status[$code]:$status[500];
    }

    private function getBaseURL() {
        return sprintf(
            "%s://%s",
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
            $_SERVER['HTTP_HOST']
        );
    }

    private function untrailingslashit( $string ) {
        return rtrim( $string, '/\\' );
    }

    private function trailingslashit( $string ) {
        return $this->untrailingslashit( $string ) . '/';
    }
}
