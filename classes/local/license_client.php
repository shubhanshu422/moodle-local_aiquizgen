<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_aiquizgen\local;

/**
 * Client for explicitly enabled, optional signed external services.
 *
 * This client never gates core quiz-generation functionality.
 *
 * @package   local_aiquizgen
 * @copyright 2026 AI Quiz Generator contributors
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class license_client {
    /** @var string[] Recognized optional-service states. */
    private const STATUSES = ['active', 'expired', 'suspended', 'revoked', 'invalid'];

    /**
     * Activates the optional service.
     *
     * @return array Verified activation response payload.
     */
    public function activate(): array {
        return $this->request('activate');
    }

    /**
     * Validates the optional service.
     *
     * @return array Verified validation response payload.
     */
    public function validate(): array {
        return $this->request('validate');
    }

    /**
     * Deactivates the optional service.
     *
     * @return array Verified deactivation response payload.
     */
    public function deactivate(): array {
        $result = $this->request('deactivate');
        set_config('license_status', 'invalid', 'local_aiquizgen');
        return $result;
    }

    /**
     * Sends an optional-service operation and verifies its signed response.
     *
     * @param string $operation Operation name.
     * @return array Verified response payload.
     */
    private function request(string $operation): array {
        global $CFG;
        if (!get_config('local_aiquizgen', 'enableoptionalservices')) {
            throw new \moodle_exception('optionalservicesdisabled', 'local_aiquizgen');
        }
        $serverurl = rtrim((string)get_config('local_aiquizgen', 'licenseserverurl'), '/');
        $licensekey = trim((string)get_config('local_aiquizgen', 'licensekey'));
        $publickey = trim((string)get_config('local_aiquizgen', 'licensepublickey'));
        if ($serverurl === '' || $licensekey === '' || $publickey === '') {
            throw new \moodle_exception('licenseconfigurationmissing', 'local_aiquizgen');
        }
        $parts = parse_url($serverurl);
        $ishostlocal = in_array($parts['host'] ?? '', ['localhost', '127.0.0.1', '::1'], true);
        if (($parts['scheme'] ?? '') !== 'https' && !$ishostlocal) {
            throw new \moodle_exception('licensehttpsrequired', 'local_aiquizgen');
        }
        require_once($CFG->libdir . '/filelib.php');
        set_config('license_lastattempt', time(), 'local_aiquizgen');
        $curl = new \curl();
        $raw = $curl->post($serverurl . '/api/v1/licenses/' . $operation, json_encode([
            'license_key' => $licensekey,
            'site_url' => $CFG->wwwroot,
            'site_identifier' => hash('sha256', strtolower(rtrim($CFG->wwwroot, '/'))),
            'component' => 'local_aiquizgen',
            'version' => get_config('local_aiquizgen', 'version') ?: null,
        ]), [
            'CURLOPT_HTTPHEADER' => ['Content-Type: application/json', 'Accept: application/json'],
            'CURLOPT_CONNECTTIMEOUT' => 10,
            'CURLOPT_TIMEOUT' => 30,
        ]);
        $httpcode = (int)($curl->get_info()['http_code'] ?? 0);
        $response = json_decode($raw, true);
        if ($httpcode < 200 || $httpcode >= 300 || !is_array($response)) {
            set_config('license_lasterror', 'HTTP ' . $httpcode, 'local_aiquizgen');
            throw new \moodle_exception('licenseserverunavailable', 'local_aiquizgen');
        }
        try {
            $payload = self::verify_signed_response($response, $publickey);
        } catch (\moodle_exception $exception) {
            set_config('license_lasterror', 'Invalid server signature', 'local_aiquizgen');
            throw $exception;
        }
        $status = self::normalise_status((string)($payload['status'] ?? 'invalid'));
        set_config('license_status', $status, 'local_aiquizgen');
        set_config('license_plan', clean_param($payload['plan'] ?? '', PARAM_TEXT), 'local_aiquizgen');
        set_config('license_expiresat', (int)($payload['expires_at'] ?? 0), 'local_aiquizgen');
        set_config('license_activationlimit', (int)($payload['activation_limit'] ?? 0), 'local_aiquizgen');
        set_config('license_activations', (int)($payload['activations'] ?? 0), 'local_aiquizgen');
        set_config('license_message', clean_param($payload['message'] ?? '', PARAM_TEXT), 'local_aiquizgen');
        set_config('license_lastsuccess', time(), 'local_aiquizgen');
        unset_config('license_lasterror', 'local_aiquizgen');
        return $payload;
    }

    /**
     * Verifies and decodes a signed response envelope.
     *
     * @param array $response Response envelope.
     * @param string $publickey PEM public key.
     * @return array Decoded payload.
     */
    public static function verify_signed_response(array $response, string $publickey): array {
        $payloadjson = base64_decode((string)($response['payload'] ?? ''), true);
        $signature = base64_decode((string)($response['signature'] ?? ''), true);
        if (
            $payloadjson === false || $signature === false ||
                openssl_verify($payloadjson, $signature, $publickey, OPENSSL_ALGO_SHA256) !== 1
        ) {
            throw new \moodle_exception('licenseinvalidsignature', 'local_aiquizgen');
        }
        $payload = json_decode($payloadjson, true);
        if (!is_array($payload)) {
            throw new \moodle_exception('licenseinvalidsignature', 'local_aiquizgen');
        }
        return $payload;
    }

    /**
     * Maps an external status to a recognized internal value.
     *
     * @param string $status External status.
     * @return string
     */
    public static function normalise_status(string $status): string {
        $status = strtolower($status);
        return in_array($status, self::STATUSES, true) ? $status : 'invalid';
    }
}
