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

namespace local_aiquizgen;

/**
 * Tests signature verification for explicitly optional external services.
 *
 * @package   local_aiquizgen
 * @copyright 2026 AI Quiz Generator contributors
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class license_client_test extends \advanced_testcase {
    public function test_valid_signed_response_is_accepted(): void {
        $keys = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($keys, $privatekey);
        $publickey = openssl_pkey_get_details($keys)['key'];
        $payload = json_encode(['status' => 'active', 'plan' => 'Professional']);
        openssl_sign($payload, $signature, $privatekey, OPENSSL_ALGO_SHA256);
        $decoded = \local_aiquizgen\local\license_client::verify_signed_response([
            'payload' => base64_encode($payload),
            'signature' => base64_encode($signature),
        ], $publickey);
        $this->assertSame('active', $decoded['status']);
        $this->assertSame('Professional', $decoded['plan']);
    }

    public function test_invalid_signature_is_rejected(): void {
        $keys = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $publickey = openssl_pkey_get_details($keys)['key'];
        $this->expectException(\moodle_exception::class);
        \local_aiquizgen\local\license_client::verify_signed_response([
            'payload' => base64_encode('{"status":"active"}'),
            'signature' => base64_encode('invalid'),
        ], $publickey);
    }

    public function test_unknown_status_is_invalid(): void {
        $this->assertSame('invalid', \local_aiquizgen\local\license_client::normalise_status('unknown-plan-state'));
        $this->assertSame('expired', \local_aiquizgen\local\license_client::normalise_status('EXPIRED'));
    }
}
