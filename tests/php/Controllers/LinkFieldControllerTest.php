<?php

namespace SilverStripe\LinkField\Tests\Controllers;

use SilverStripe\Dev\FunctionalTest;
use SilverStripe\LinkField\Tests\Controllers\LinkFieldControllerTest\TestPhoneLink;
use SilverStripe\Core\Config\Config;
use SilverStripe\Security\SecurityToken;
use SilverStripe\Control\HTTPRequest;

class LinkFieldControllerTest extends FunctionalTest
{
    protected static $fixture_file = 'LinkFieldControllerTest.yml';

    protected static $extra_dataobjects = [
        TestPhoneLink::class,
    ];

    private $securityTokenWasEnabled = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logInWithPermission('ADMIN');
        // CSRF token check is normally disabled for unit-tests
        $this->securityTokenWasEnabled = SecurityToken::is_enabled();
        if (!$this->securityTokenWasEnabled) {
            SecurityToken::enable();
        }
        TestPhoneLink::$fail = '';
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (!$this->securityTokenWasEnabled) {
            SecurityToken::disable();
        }
    }

    /**
     * @dataProvider provideLinkFormGetSchema
     */
    public function testLinkFormGetSchema(
        string $idType,
        string $typeKey,
        string $fail,
        int $expectedCode,
        ?string $expectedValue,
        string $expectedMessage
    ): void {
        TestPhoneLink::$fail = $fail;
        $id = $this->getID($idType);
        if ($id === -1) {
            $url = "/admin/linkfield/schema/linkForm?typeKey=$typeKey";
        } else {
            $url = "/admin/linkfield/schema/linkForm/$id?typeKey=$typeKey";
        }
        $headers = $this->formSchemaHeader();
        $response = $this->get($url, null, $headers);
        $this->assertSame('application/json', $response->getHeader('Content-type'));
        $this->assertSame($expectedCode, $response->getStatusCode());
        if ($expectedCode !== 200) {
            $jsonError = json_decode($response->getBody(), true);
            $this->assertSame($expectedMessage, $jsonError['errors'][0]['value']);
        } else {
            $formSchema = json_decode($response->getBody(), true);
            $this->assertSame("admin/linkfield/schema/linkForm/$id", $formSchema['id']);
            $this->assertSame("admin/linkfield/linkForm/$id?typeKey=testphone", $formSchema['schema']['action']);
            // schema is nested and retains 'Root' and 'Main' tab hierarchy
            $this->assertSame('Phone', $formSchema['schema']['fields'][0]['children'][0]['children'][2]['name']);
            $this->assertSame('action_save', $formSchema['schema']['actions'][0]['name']);
            // state node is flattened, unlike schema node
            $this->assertSame($expectedValue, $formSchema['state']['fields'][4]['value']);
            $this->assertFalse(array_key_exists('errors', $formSchema));
        }
    }

    public function provideLinkFormGetSchema(): array
    {
        return [
            'Valid existing record' => [
                'idType' => 'existing',
                'typeKey' => 'testphone',
                'fail' => '',
                'expectedCode' => 200,
                'expectedValue' => '0123456789',
                'expectedMessage' => '',
            ],
            'Valid new record' => [
                'idType' => 'new-record',
                'typeKey' => 'testphone',
                'fail' => '',
                'expectedCode' => 200,
                'expectedValue' => null,
                'expectedMessage' => '',
            ],
            'Reject invalid ID' => [
                'idType' => 'invalid',
                'typeKey' => 'testphone',
                'fail' => '',
                'expectedCode' => 404,
                'expectedValue' => null,
                'expectedMessage' => 'Invalid ID',
            ],
            'Reject missing ID' => [
                'idType' => 'missing',
                'typeKey' => 'testphone',
                'fail' => '',
                'expectedCode' => 404,
                'expectedValue' => null,
                'expectedMessage' => 'Invalid ID',
            ],
            'Reject non-numeric ID' => [
                'idType' => 'non-numeric',
                'typeKey' => 'testphone',
                'fail' => '',
                'expectedCode' => 404,
                'expectedValue' => null,
                'expectedMessage' => 'Invalid ID',
            ],
            'Reject invalid typeKey for new record' => [
                'idType' => 'new-record',
                'typeKey' => 'donut',
                'fail' => '',
                'expectedCode' => 404,
                'expectedValue' => null,
                'expectedMessage' => 'Invalid typeKey',
            ],
            'Reject fail canView() check' => [
                'idType' => 'existing',
                'typeKey' => 'testphone',
                'fail' => 'can-view',
                'expectedCode' => 403,
                'expectedValue' => null,
                'expectedMessage' => 'Unauthorized',
            ],
        ];
    }

    /**
     * @dataProvider provideLinkFormPost
     */
    public function testLinkFormPost(
        string $idType,
        string $typeKey,
        string $dataType,
        string $fail,
        int $expectedCode,
        string $expectedMessage,
        string $expectedLinkType
    ): void {
        TestPhoneLink::$fail = $fail;
        $id = $this->getID($idType);
        if ($dataType === 'valid') {
            $data = $this->getFixtureLink()->jsonSerialize();
            $data['Phone'] = '9876543210';
            $data['ID'] = $id;
        } elseif ($dataType === 'invalid-id') {
            $data = $this->getFixtureLink()->jsonSerialize();
            $data['Phone'] = '9876543210';
            $data['ID'] = $id + 99999;
        } else {
            $data = [];
        }
        if ($fail) {
            $data['Fail'] = $fail;
        }
        $url = "/admin/linkfield/linkForm/$id?typeKey=$typeKey";
        $headers = $this->formSchemaHeader();
        if ($fail !== 'csrf-token') {
            $headers = array_merge($headers, $this->csrfTokenheader());
        }
        $response = $this->post($url, $data, $headers);
        $this->assertSame($expectedCode, $response->getStatusCode());
        if ($fail === 'csrf-token') {
            // Will end up at an HTML page with "Silverstripe - Bad Request"
            $this->assertSame('text/html; charset=utf-8', $response->getHeader('Content-type'));
            $this->assertStringContainsString('Silverstripe - Bad Request', $response->getBody());
            return;
        }
        $this->assertSame('application/json', $response->getHeader('Content-type'));
        if ($expectedCode !== 200) {
            $jsonError = json_decode($response->getBody(), true);
            $this->assertSame($expectedMessage, $jsonError['errors'][0]['value']);
        } else {
            $formSchema = json_decode($response->getBody(), true);
            $newID = $this->getIDAfterPost($expectedLinkType);
            if ($expectedLinkType === 'new-record') {
                $this->assertNotSame($id, $newID);
            } else {
                $this->assertSame($id, $newID);
            }
            if ($fail) {
                $this->assertSame("admin/linkfield/schema/linkfield/$newID", $formSchema['id']);
            } else {
                $this->assertSame("admin/linkfield/linkForm/$newID?typeKey=testphone", $formSchema['id']);
            }
            $this->assertSame("admin/linkfield/linkForm/$newID?typeKey=testphone", $formSchema['schema']['action']);
            // schema is nested and retains 'Root' and 'Main' tab hierarchy
            $this->assertSame('Phone', $formSchema['schema']['fields'][0]['children'][0]['children'][2]['name']);
            $this->assertSame('action_save', $formSchema['schema']['actions'][0]['name']);
            // state node is flattened, unlike schema node
            $this->assertSame('9876543210', $formSchema['state']['fields'][4]['value']);
            if ($fail) {
                $this->assertSame($expectedMessage, $formSchema['errors'][0]['value']);
                // Phone was note updated on PhoneLink dataobject
                $link = TestPhoneLink::get()->byID($newID);
                $this->assertSame($link->Phone, '0123456789');
            } else {
                $this->assertEmpty($formSchema['errors']);
                // Phone was updated on PhoneLink dataobject
                $link = TestPhoneLink::get()->byID($newID);
                $this->assertSame($link->Phone, '9876543210');
            }
        }
    }

    public function provideLinkFormPost(): array
    {
        // note: not duplicating code paths already tested with provideLinkFormGetSchema()
        // e.g. Reject Invalid ID
        return [
            'Valid update existing record' => [
                'idType' => 'existing',
                'typeKey' => 'testphone',
                'dataType' => 'valid',
                'fail' => '',
                'expectedCode' => 200,
                'expectedMessage' => '',
                'expectedLinkType' => 'existing',
            ],
            'Valid create new record' => [
                'idType' => 'new-record',
                'typeKey' => 'testphone',
                'dataType' => 'valid',
                'fail' => '',
                'expectedCode' => 200,
                'expectedMessage' => '',
                'expectedLinkType' => 'new-record',
            ],
            'Invalid validate()' => [
                'idType' => 'existing',
                'typeKey' => 'testphone',
                'dataType' => 'valid',
                'fail' => 'validate',
                'expectedCode' => 200,
                'expectedMessage' => 'Fail was validate',
                'expectedLinkType' => 'existing',
            ],
            'Invalid getCMSCompositeValidator()' => [
                'idType' => 'existing',
                'typeKey' => 'testphone',
                'dataType' => 'valid',
                'fail' => 'cms-composite-validator',
                'expectedCode' => 200,
                'expectedMessage' => 'Fail was cms-composite-validator',
                'expectedLinkType' => 'existing',
            ],
            'Reject invalid ID' => [
                'idType' => 'invalid',
                'typeKey' => 'testphone',
                'dataType' => 'valid',
                'fail' => '',
                'expectedCode' => 404,
                'expectedMessage' => 'Invalid ID',
                'expectedLinkType' => '',
            ],
            'Reject missing ID' => [
                'idType' => 'missing',
                'typeKey' => 'testphone',
                'dataType' => 'valid',
                'fail' => '',
                'expectedCode' => 404,
                'expectedMessage' => 'Invalid ID',
                'expectedLinkType' => '',
            ],
            'Reject non-numeric ID' => [
                'idType' => 'non-numeric',
                'typeKey' => 'testphone',
                'dataType' => 'valid',
                'fail' => '',
                'expectedCode' => 404,
                'expectedMessage' => 'Invalid ID',
                'expectedLinkType' => '',
            ],
            'Reject invalid typeKey for new record' => [
                'idType' => 'new-record',
                'typeKey' => 'donut',
                'dataType' => 'valid',
                'fail' => '',
                'expectedCode' => 404,
                'expectedMessage' => 'Invalid typeKey',
                'expectedLinkType' => '',
            ],
            'Reject empty data' => [
                'idType' => 'existing',
                'typeKey' => 'testphone',
                'dataType' => 'empty',
                'fail' => '',
                'expectedCode' => 400,
                'expectedMessage' => 'Empty data',
                'expectedLinkType' => '',
            ],
            'Reject invalid-id data' => [
                'idType' => 'existing',
                'typeKey' => 'testphone',
                'dataType' => 'invalid-id',
                'fail' => '',
                'expectedCode' => 400,
                'expectedMessage' => 'Bad data',
                'expectedLinkType' => '',
            ],
            'Reject fail csrf-token' => [
                'idType' => 'existing',
                'typeKey' => 'testphone',
                'dataType' => 'valid',
                'fail' => 'csrf-token',
                'expectedCode' => 400,
                'expectedMessage' => 'Invalid CSRF token',
                'expectedLinkType' => '',
            ],
            'Reject fail canEdit() check existing record' => [
                'idType' => 'existing',
                'typeKey' => 'testphone',
                'dataType' => 'valid',
                'fail' => 'can-edit',
                'expectedCode' => 403,
                'expectedMessage' => 'Unauthorized',
                'expectedLinkType' => '',
            ],
            'Reject fail canCreate() check new record' => [
                'idType' => 'new-record',
                'typeKey' => 'testphone',
                'dataType' => 'valid',
                'fail' => 'can-create',
                'expectedCode' => 403,
                'expectedMessage' => 'Unauthorized',
                'expectedLinkType' => '',
            ],
        ];
    }

    /**
     * @dataProvider provideLinkFormReadOnly
     */
    public function testLinkFormReadonly(string $idType, string $fail, bool $expected): void
    {
        TestPhoneLink::$fail = $fail;
        $id = $this->getID($idType);
        $typeKey = 'testphone';
        $url = "/admin/linkfield/schema/linkForm/$id?typeKey=$typeKey";
        $headers = $this->formSchemaHeader();
        $body = $this->get($url, null, $headers)->getBody();
        $json = json_decode($body, true);
        $actual = $json['schema']['fields'][0]['children'][0]['readOnly'] ?? false;
        $this->assertSame($expected, $actual);
    }

    public function provideLinkFormReadOnly(): array
    {
        return [
            [
                'idType' => 'existing',
                'fail' => '',
                "expected" => false,
            ],
            [
                'idType' => 'existing',
                'fail' => 'can-edit',
                "expected" => true,
            ],
            [
                'idType' => 'new-record',
                'fail' => '',
                "expected" => false,
            ],
            [
                'idType' => 'new-record',
                'fail' => 'can-create',
                "expected" => true,
            ],
        ];
    }

    /**
     * @dataProvider provideLinkData
     */
    public function testLinkData(
        string $idType,
        int $expectedCode,
        string $expectedMessage,
    ): void {
        $id = $this->getID($idType);
        if ($id === -1) {
            $url = "/admin/linkfield/data";
        } else {
            $url = "/admin/linkfield/data/$id";
        }
        $response = $this->get($url);
        $this->assertSame('application/json', $response->getHeader('Content-type'));
        $this->assertSame($expectedCode, $response->getStatusCode());
        if ($expectedCode !== 200) {
            $jsonError = json_decode($response->getBody(), true);
            $this->assertSame($expectedMessage, $jsonError['errors'][0]['value']);
        } else {
            $data = json_decode($response->getBody(), true);
            $this->assertSame($id, $data['ID']);
            $this->assertSame('0123456789', $data['Phone']);
            $link = $this->getFixtureLink();
            $this->assertSame($link->getVersionedState(), $data['versionState']);
        }
    }

    public function provideLinkData(): array
    {
        return [
            'Valid' => [
                'idType' => 'existing',
                'expectedCode' => 200,
                'expectedMessage' => '',
            ],
            'Reject invalid ID' => [
                'idType' => 'invalid',
                'expectedCode' => 404,
                'expectedMessage' => 'Invalid ID',
            ],
            'Reject missing ID' => [
                'idType' => 'missing',
                'expectedCode' => 404,
                'expectedMessage' => 'Invalid ID',
            ],
            'Reject non-numeric ID' => [
                'idType' => 'non-numeric',
                'expectedCode' => 404,
                'expectedMessage' => 'Invalid ID',
            ],
            'Reject new record ID' => [
                'idType' => 'new-record',
                'expectedCode' => 404,
                'expectedMessage' => 'Invalid ID',
            ],
        ];
    }

    /**
     * @dataProvider provideLinkDelete
     */
    public function testLinkDelete(
        string $idType,
        string $fail,
        int $expectedCode,
        string $expectedMessage
    ): void {
        TestPhoneLink::$fail = $fail;
        $id = $this->getID($idType);
        $fixtureID = $this->getFixtureLink()->ID;
        if ($id === -1) {
            $url = "/admin/linkfield/delete";
        } else {
            $url = "/admin/linkfield/delete/$id";
        }
        $headers = [];
        if ($fail !== 'csrf-token') {
            $headers = array_merge($headers, $this->csrfTokenheader());
        }
        $response = $this->mainSession->sendRequest('DELETE', $url, [], $headers);
        $this->assertSame('application/json', $response->getHeader('Content-type'));
        $this->assertSame($expectedCode, $response->getStatusCode());
        if ($expectedCode !== 200) {
            $jsonError = json_decode($response->getBody(), true);
            $this->assertSame($expectedMessage, $jsonError['errors'][0]['value']);
            $this->assertNotNull(TestPhoneLink::get()->byID($fixtureID));
        } else {
            $this->assertNull(TestPhoneLink::get()->byID($fixtureID));
        }
        $this->assertTrue(true);
    }

    public function provideLinkDelete(): array
    {
        return [
            'Valid' => [
                'idType' => 'existing',
                'fail' => '',
                'expectedCode' => 200,
                'expectedMessage' => '',
            ],
            'Reject fail canDelete()' => [
                'idType' => 'existing',
                'fail' => 'can-delete',
                'expectedCode' => 403,
                'expectedMessage' => 'Unauthorized',
            ],
            'Reject fail csrf-token' => [
                'idType' => 'existing',
                'fail' => 'csrf-token',
                'expectedCode' => 400,
                'expectedMessage' => 'Invalid CSRF token',
            ],
            'Reject invalid ID' => [
                'idType' => 'invalid',
                'fail' => '',
                'expectedCode' => 404,
                'expectedMessage' => 'Invalid ID',
            ],
            'Reject missing ID' => [
                'idType' => 'missing',
                'fail' => '',
                'expectedCode' => 404,
                'expectedMessage' => 'Invalid ID',
            ],
            'Reject non-numeric ID' => [
                'idType' => 'non-numeric',
                'fail' => '',
                'expectedCode' => 404,
                'expectedMessage' => 'Invalid ID',
            ],
            'Reject new record ID' => [
                'idType' => 'new-record',
                'fail' => '',
                'expectedCode' => 404,
                'expectedMessage' => 'Invalid ID',
            ],
        ];
    }

    private function getFixtureLink(): TestPhoneLink
    {
        return $this->objFromFixture(TestPhoneLink::class, 'TestPhoneLink01');
    }

    private function getID(string $idType): mixed
    {
        $link = $this->getFixtureLink();
        return match ($idType) {
            'existing' => $link->ID,
            'invalid' => $link->ID + 99999,
            'missing' => -1,
            'non-numeric' => 'fish',
            'new-record' => 0,
        };
    }

    private function getIDAfterPost(string $expectedLinkType): int
    {
        return match ($expectedLinkType) {
            'existing' => $this->getFixtureLink()->ID,
            'new-record' => TestPhoneLink::get()->max('ID'),
        };
    }

    private function formSchemaHeader(): array
    {
        return [
            'X-FormSchema-Request' => 'auto,schema,state,errors'
        ];
    }

    private function csrfTokenheader(): array
    {
        $securityToken = SecurityToken::inst();
        return [
            'X-' . $securityToken->getName() => $securityToken->getSecurityID()
        ];
    }
}
