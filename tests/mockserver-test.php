<?php
/**
 * Class Mockserver Test
 *
 * @package GbWeiss
 * @author Towa Digital <developer@towa.at>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0-or-later
 */

 namespace Tests;

 use GuzzleHttp;

/**
 * Sample test case.
 */
class MockserverTest extends \WP_UnitTestCase
{

    /**
     * A single example test.
     */
    public function test_if_server_started()
    {
        $http = new GuzzleHttp\Client(['base_uri' => 'http://localhost:8887/']);
        $statusCode = $http->request('GET', 'jwks')->getStatusCode();
        $this->assertEquals(200, $statusCode);
    }
}
