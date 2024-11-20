<?php

namespace SilverStripe\LinkField\Tests\Controllers;

use ReflectionMethod;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\LinkField\Tests\Controllers\LinkFieldControllerTest\TestPhoneLink;
use SilverStripe\Security\SecurityToken;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\LinkField\Controllers\LinkFieldController;
use SilverStripe\LinkField\Tests\Models\LinkTest\LinkOwner;
use SilverStripe\Versioned\Versioned;
use SilverStripe\LinkField\Models\Link;
use PHPUnit\Framework\Attributes\DataProvider;

class LinkFieldControllerTest extends FunctionalTest
{
    protected static $fixture_file = 'LinkFieldControllerTest.yml';

    protected static $extra_dataobjects = [
        TestPhoneLink::class,
        LinkOwner::class,
    ];

    private $securityTokenWasEnabled = false;

    protected function setUp(): void
    {
        parent::setUp();
        // The Versioned extension is added to LinkOwner, though it's only used for the
        // unit tests speicifically to test Versioned functionality. It interferes with
        // other units tests that were written before the Versioned extension was added
        // The extenion needs to be added on the class rather than dynmically so that the
        // relevant database tables are created. The extension is added back in on unit
        // tests that requrie it. There's a call to add the extension back in the
        // tearDown method
        LinkOwner::remove_extension(Versioned::class);

        $this->logInWithPermission('ADMIN');
        // CSRF token check is normally disabled for unit-tests
        $this->securityTokenWasEnabled = SecurityToken::is_enabled();
        if (!$this->securityTokenWasEnabled) {
            SecurityToken::enable();
        }
        TestPhoneLink::$fail = '';
        // Manually add fixture link to owner. Cannot do this in yml as you cannot have duplicate keys for
        // SilverStripe\LinkField\Tests\Models\LinkTest\LinkOwner as would be required to join the Links to Owners
        // for both the has_many as well as the has_one relations
        $link = $this->getFixtureLink();
        $owner = $this->getFixtureLinkOwner();
        $owner->Link = $link;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (!$this->securityTokenWasEnabled) {
            SecurityToken::disable();
        }
        // Reset the extension that was removed in the setUp() method
        LinkOwner::add_extension(Versioned::class);
    }

    #[DataProvider('provideLinkFormGetSchema')]
    public function testLinkFormGetSchema(
        string $idType,
        string $typeKey,
        string $fail,
        int $expectedCode,
        ?string $expectedValue
    ): void {
        TestPhoneLink::$fail = $fail;
        $owner = $this->getFixtureLinkOwner();
        $ownerID = $owner->ID;
        $ownerClass = urlencode($owner->ClassName);
        $ownerRelation = 'Link';
        $id = $this->getID($idType);
        if ($id === -1) {
            $url = "/admin/linkfield/schema/linkForm?typeKey=$typeKey&ownerID=$ownerID&ownerClass=$ownerClass"
                . "&ownerRelation=$ownerRelation";
        } else {
            $url = "/admin/linkfield/schema/linkForm/$id?typeKey=$typeKey&ownerID=$ownerID&ownerClass=$ownerClass"
                . "&ownerRelation=$ownerRelation";
        }
        $headers = $this->formSchemaHeader();
        $response = $this->get($url, null, $headers);
        $this->assertSame('application/json', $response->getHeader('Content-type'));
        $this->assertSame($expectedCode, $response->getStatusCode());
        if ($expectedCode === 200) {
            $formSchema = json_decode($response->getBody(), true);
            $this->assertSame("admin/linkfield/schema/linkForm/$id", $formSchema['id']);
            $expectedAction = "admin/linkfield/linkForm/$id?typeKey=testphone&ownerID=$ownerID&ownerClass=$ownerClass"
                . "&ownerRelation=$ownerRelation";
            $this->assertSame($expectedAction, $formSchema['schema']['action']);
            // schema is nested and retains 'Root' and 'Main' tab hierarchy
            $this->assertSame('Phone', $formSchema['schema']['fields'][0]['children'][0]['children'][0]['name']);
            $this->assertSame('action_save', $formSchema['schema']['actions'][0]['name']);
            // state node is flattened, unlike schema node
            $this->assertSame($expectedValue, $formSchema['state']['fields'][2]['value']);
            $this->assertFalse(array_key_exists('errors', $formSchema));
            if ($idType === 'new-record') {
                $this->assertSame('OwnerID', $formSchema['state']['fields'][6]['name']);
                $this->assertSame($ownerID, $formSchema['state']['fields'][6]['value']);
                $this->assertSame('OwnerClass', $formSchema['state']['fields'][7]['name']);
                $this->assertSame($owner->ClassName, $formSchema['state']['fields'][7]['value']);
                $this->assertSame('OwnerRelation', $formSchema['state']['fields'][8]['name']);
                $this->assertSame($ownerRelation, $formSchema['state']['fields'][8]['value']);
            } else {
                $this->assertNotSame('OwnerID', $formSchema['state']['fields'][6]['name']);
                $this->assertFalse(array_key_exists(7, $formSchema['state']['fields']));
                $this->assertFalse(array_key_exists(8, $formSchema['state']['fields']));
            }
        }
    }

    public static function provideLinkFormGetSchema(): array
    {
        return [
            'Valid existing record' => [
                'idType' => 'existing',
                'typeKey' => 'testphone',
                'fail' => '',
                'expectedCode' => 200,
                'expectedValue' => '0123456789',
            ],
            'Valid new record' => [
                'idType' => 'new-record',
                'typeKey' => 'testphone',
                'fail' => '',
                'expectedCode' => 200,
                'expectedValue' => null,
            ],
            'Reject invalid ID' => [
                'idType' => 'invalid',
                'typeKey' => 'testphone',
                'fail' => '',
                'expectedCode' => 404,
                'expectedValue' => null,
            ],
            'Reject missing ID' => [
                'idType' => 'missing',
                'typeKey' => 'testphone',
                'fail' => '',
                'expectedCode' => 404,
                'expectedValue' => null,
            ],
            'Reject non-numeric ID' => [
                'idType' => 'non-numeric',
                'typeKey' => 'testphone',
                'fail' => '',
                'expectedCode' => 404,
                'expectedValue' => null,
            ],
            'Reject invalid typeKey for new record' => [
                'idType' => 'new-record',
                'typeKey' => 'donut',
                'fail' => '',
                'expectedCode' => 404,
                'expectedValue' => null,
            ],
            'Reject fail canView() check' => [
                'idType' => 'existing',
                'typeKey' => 'testphone',
                'fail' => 'can-view',
                'expectedCode' => 403,
                'expectedValue' => null,
            ],
        ];
    }

    #[DataProvider('provideExcludeLinkTextField')]
    public function testExcludeLinkTextField(bool $excludeLinkTextField): void
    {
        $owner = $this->getFixtureLinkOwner();
        $itemID = $this->getID('existing');
        $vars = [
            'ownerID' => $owner->ID,
            'ownerClass' => $owner->ClassName,
            'ownerRelation' => 'Link',
        ];
        if ($excludeLinkTextField) {
            $vars['excludeLinkTextField'] = true;
        }
        // Build request and controller to get the scaffolded form
        $request = new HTTPRequest('GET', 'linkForm/' . $itemID, getVars: $vars);
        $request->setSession(new Session([]));
        $controller = new LinkFieldController();
        $reflectionFindAction = new ReflectionMethod($controller, 'findAction');
        $reflectionFindAction->setAccessible(true);
        $reflectionFindAction->invoke($controller, $request);
        $controller->setRequest($request);
        $form = $controller->linkForm();
        // Check if the field is there or not
        if ($excludeLinkTextField) {
            $this->assertNull($form->Fields()->dataFieldByName('LinkText'));
        } else {
            $this->assertNotNull($form->Fields()->dataFieldByName('LinkText'));
        }
    }

    public static function provideExcludeLinkTextField(): array
    {
        return [
            'exclude field' => [
                'excludeLinkTextField' => true,
            ],
            'dont exclude field' => [
                'excludeLinkTextField' => false,
            ],
        ];
    }

    #[DataProvider('provideLinkFormPost')]
    public function testLinkFormPost(
        string $idType,
        string $typeKey,
        string $dataType,
        string $fail,
        int $expectedCode,
        string $expectedLinkType
    ): void {
        TestPhoneLink::$fail = $fail;
        $owner = $this->getFixtureLinkOwner();
        $ownerID = $owner->ID;
        $ownerClass = urlencode($owner->ClassName);
        $ownerRelation = 'Link';
        $ownerLinkID = $owner->LinkID;
        $id = $this->getID($idType);
        if ($dataType === 'valid') {
            $data = $this->getFixtureLink()->toMap();
            $data['Phone'] = '9876543210';
            $data['ID'] = $id;
        } elseif ($dataType === 'invalid-id') {
            $data = $this->getFixtureLink()->toMap();
            $data['Phone'] = '9876543210';
            $data['ID'] = $id + 99999;
        } else {
            $data = [];
        }
        unset($data['Sort']);
        if ($fail) {
            $data['Fail'] = $fail;
        }
        $url = "/admin/linkfield/linkForm/$id?typeKey=$typeKey&ownerID=$ownerID&ownerClass=$ownerClass"
            . "&ownerRelation=$ownerRelation";
        $headers = $this->formSchemaHeader();
        if ($fail !== 'csrf-token') {
            $headers = array_merge($headers, $this->csrfTokenheader());
        }
        $response = $this->post($url, $data, $headers);
        $this->assertSame($expectedCode, $response->getStatusCode());
        if ($fail === 'csrf-token') {
            // Gives suitable error message for XHR request
            $this->assertStringStartsWith('text/plain', $response->getHeader('Content-type'));
            $this->assertStringContainsString('There seems to have been a technical problem', $response->getBody());
            return;
        }
        $this->assertSame($expectedCode, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeader('Content-type'));
        if ($expectedCode === 200) {
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
                $expectedUrl = "admin/linkfield/linkForm/$newID?typeKey=testphone&ownerID=$ownerID"
                    . "&ownerClass=$ownerClass&ownerRelation=$ownerRelation";
                $this->assertSame($expectedUrl, $formSchema['id']);
            }
            $expectedUrl = "admin/linkfield/linkForm/$newID?typeKey=testphone&ownerID=$ownerID&ownerClass=$ownerClass"
                . "&ownerRelation=$ownerRelation";
            $this->assertSame($expectedUrl, $formSchema['schema']['action']);
            // schema is nested and retains 'Root' and 'Main' tab hierarchy
            $this->assertSame('Phone', $formSchema['schema']['fields'][0]['children'][0]['children'][0]['name']);
            $this->assertSame('action_save', $formSchema['schema']['actions'][0]['name']);
            // state node is flattened, unlike schema node
            $this->assertSame('9876543210', $formSchema['state']['fields'][2]['value']);
            if ($fail) {
                // Phone was note updated on PhoneLink dataobject
                $link = TestPhoneLink::get()->byID($newID);
                $this->assertSame($link->Phone, '0123456789');
                // LinkOwner.Link relation was not updated (refetch dataobject first)
                $owner = $this->getFixtureLinkOwner();
                $this->assertSame($owner->LinkID, $ownerLinkID);
                if ($idType === 'new-record') {
                    $this->assertsame($newID, $ownerLinkID);
                }
            } else {
                $this->assertEmpty($formSchema['errors']);
                // Phone was updated on PhoneLink dataobject
                $link = TestPhoneLink::get()->byID($newID);
                $this->assertSame($link->Phone, '9876543210');
                // LinkOwner.Link relation was updated (refetch dataobject first)
                $owner = $this->getFixtureLinkOwner();
                $this->assertSame($newID, $owner->LinkID);
                if ($idType === 'new-record') {
                    $this->assertNotSame($newID, $ownerLinkID);
                }
            }
        }
    }

    public static function provideLinkFormPost(): array
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
                'expectedLinkType' => 'existing',
            ],
            'Valid create new record' => [
                'idType' => 'new-record',
                'typeKey' => 'testphone',
                'dataType' => 'valid',
                'fail' => '',
                'expectedCode' => 200,
                'expectedLinkType' => 'new-record',
            ],
            'Invalid validate()' => [
                'idType' => 'existing',
                'typeKey' => 'testphone',
                'dataType' => 'valid',
                'fail' => 'validate',
                'expectedCode' => 200,
                'expectedLinkType' => 'existing',
            ],
            'Invalid getCMSCompositeValidator()' => [
                'idType' => 'existing',
                'typeKey' => 'testphone',
                'dataType' => 'valid',
                'fail' => 'cms-composite-validator',
                'expectedCode' => 200,
                'expectedLinkType' => 'existing',
            ],
            'Reject invalid ID' => [
                'idType' => 'invalid',
                'typeKey' => 'testphone',
                'dataType' => 'valid',
                'fail' => '',
                'expectedCode' => 404,
                'expectedLinkType' => '',
            ],
            'Reject missing ID' => [
                'idType' => 'missing',
                'typeKey' => 'testphone',
                'dataType' => 'valid',
                'fail' => '',
                'expectedCode' => 404,
                'expectedLinkType' => '',
            ],
            'Reject non-numeric ID' => [
                'idType' => 'non-numeric',
                'typeKey' => 'testphone',
                'dataType' => 'valid',
                'fail' => '',
                'expectedCode' => 404,
                'expectedLinkType' => '',
            ],
            'Reject invalid typeKey for new record' => [
                'idType' => 'new-record',
                'typeKey' => 'donut',
                'dataType' => 'valid',
                'fail' => '',
                'expectedCode' => 404,
                'expectedLinkType' => '',
            ],
            'Reject empty data' => [
                'idType' => 'existing',
                'typeKey' => 'testphone',
                'dataType' => 'empty',
                'fail' => '',
                'expectedCode' => 400,
                'expectedLinkType' => '',
            ],
            'Reject invalid-id data' => [
                'idType' => 'existing',
                'typeKey' => 'testphone',
                'dataType' => 'invalid-id',
                'fail' => '',
                'expectedCode' => 400,
                'expectedLinkType' => '',
            ],
            'Reject fail csrf-token' => [
                'idType' => 'existing',
                'typeKey' => 'testphone',
                'dataType' => 'valid',
                'fail' => 'csrf-token',
                'expectedCode' => 400,
                'expectedLinkType' => '',
            ],
            'Reject fail canEdit() check existing record' => [
                'idType' => 'existing',
                'typeKey' => 'testphone',
                'dataType' => 'valid',
                'fail' => 'can-edit',
                'expectedCode' => 403,
                'expectedLinkType' => '',
            ],
            'Reject fail canCreate() check new record' => [
                'idType' => 'new-record',
                'typeKey' => 'testphone',
                'dataType' => 'valid',
                'fail' => 'can-create',
                'expectedCode' => 403,
                'expectedLinkType' => '',
            ],
        ];
    }

    #[DataProvider('provideLinkFormReadOnly')]
    public function testLinkFormReadonly(string $idType, string $fail, bool $expected): void
    {
        TestPhoneLink::$fail = $fail;
        $owner = $this->getFixtureLinkOwner();
        $ownerID = $owner->ID;
        $ownerClass = urlencode($owner->ClassName);
        $ownerRelation = 'Link';
        $id = $this->getID($idType);
        $typeKey = 'testphone';
        $url = "/admin/linkfield/schema/linkForm/$id?typeKey=$typeKey&ownerID=$ownerID&ownerClass=$ownerClass"
            . "&ownerRelation=$ownerRelation";
        $headers = $this->formSchemaHeader();
        $body = $this->get($url, null, $headers)->getBody();
        $json = json_decode($body, true);
        $actual = $json['schema']['fields'][0]['children'][0]['readOnly'] ?? false;
        $this->assertSame($expected, $actual);
    }

    public static function provideLinkFormReadOnly(): array
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

    #[DataProvider('provideLinkData')]
    public function testLinkData(
        string $idType,
        int $expectedCode,
        array $expectedData = []
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
        if ($expectedCode === 200) {
            $data = json_decode($response->getBody(), true);
            $this->assertEquals($expectedData, $data);
            $link = $this->getFixtureLink();
            $this->assertSame($link->getVersionedState(), $data['versionState']);
        }
    }

    public static function provideLinkData(): array
    {
        return [
            'Valid' => [
                'idType' => 'existing',
                'expectedCode' => 200,
                'expectedData' => [
                    'title' => 'My phone link 01',
                    'description' => '0123456789',
                    'canDelete' => true,
                    'versionState' => 'draft',
                    'typeKey' => 'testphone',
                    'sort' => 1,
                    'statusFlags' => [
                        'addedtodraft' => [
                            'text' => 'Draft',
                            'title' => 'Item has not been published yet',
                        ],
                    ],
                ],
            ],
            'Reject invalid ID' => [
                'idType' => 'invalid',
                'expectedCode' => 404,
            ],
            'Reject missing ID' => [
                'idType' => 'missing',
                'expectedCode' => 404,
            ],
            'Reject non-numeric ID' => [
                'idType' => 'non-numeric',
                'expectedCode' => 404,
            ],
            'Reject new record ID' => [
                'idType' => 'new-record',
                'expectedCode' => 404,
            ],
        ];
    }

    #[DataProvider('provideLinkArchive')]
    public function testLinkArchive(
        string $idType,
        string $fail,
        int $expectedCode
    ): void {
        TestPhoneLink::$fail = $fail;
        $owner = $this->getFixtureLinkOwner();
        $ownerID = $owner->ID;
        $ownerClass = urlencode($owner->ClassName);
        $ownerRelation = 'Link';
        $ownerLinkID = $owner->LinkID;
        $id = $this->getID($idType);
        $fixtureID = $this->getFixtureLink()->ID;
        if ($id === -1) {
            $url = "/admin/linkfield/delete?ownerID=$ownerID&ownerClass=$ownerClass&ownerRelation=$ownerRelation";
        } else {
            $url = "/admin/linkfield/delete/$id?ownerID=$ownerID&ownerClass=$ownerClass&ownerRelation=$ownerRelation";
        }
        $headers = [];
        if ($fail !== 'csrf-token') {
            $headers = array_merge($headers, $this->csrfTokenheader());
        }
        $response = $this->mainSession->sendRequest('DELETE', $url, [], $headers);
        $this->assertSame('application/json', $response->getHeader('Content-type'));
        $this->assertSame($expectedCode, $response->getStatusCode());
        if ($expectedCode >= 400) {
            $this->assertNotNull(TestPhoneLink::get()->byID($fixtureID));
        } else {
            $this->assertNull(TestPhoneLink::get()->byID($fixtureID));
        }
        if ($fail == 'can-delete') {
            Link::add_extension(Versioned::class);
        }
    }

    public static function provideLinkArchive(): array
    {
        return [
            'Valid' => [
                'idType' => 'existing',
                'fail' => '',
                'expectedCode' => 204,
            ],
            'Reject fail canDelete()' => [
                'idType' => 'existing',
                'fail' => 'can-delete',
                'expectedCode' => 403,
            ],
            'Reject fail csrf-token' => [
                'idType' => 'existing',
                'fail' => 'csrf-token',
                'expectedCode' => 400,
            ],
            'Reject invalid ID' => [
                'idType' => 'invalid',
                'fail' => '',
                'expectedCode' => 404,
            ],
            'Reject missing ID' => [
                'idType' => 'missing',
                'fail' => '',
                'expectedCode' => 404,
            ],
            'Reject non-numeric ID' => [
                'idType' => 'non-numeric',
                'fail' => '',
                'expectedCode' => 404,
            ],
            'Reject new record ID' => [
                'idType' => 'new-record',
                'fail' => '',
                'expectedCode' => 404,
            ],
        ];
    }

    public function testLinkDeleteVersions(): void
    {
        LinkOwner::add_extension(Versioned::class);
        // Need to re-link rejoin $link to $owner as the fixture data for whatever reason
        // because doing weird stuff with versioning and adding extensions
        $link = $this->getFixtureLink();
        $owner = $this->getFixtureLinkOwner();
        $owner->Link = $link;
        $link->publishSingle();
        $owner->publishSingle();
        $linkID = $link->ID;
        $ownerID = $owner->ID;
        $ownerClass = urlencode($owner->ClassName);
        $ownerRelation = 'Link';
        $url = "/admin/linkfield/delete/$linkID?ownerID=$ownerID&ownerClass=$ownerClass&ownerRelation=$ownerRelation";
        $headers = $this->csrfTokenheader();
        $liveLink = Versioned::get_by_stage($link::class, Versioned::LIVE)->byID($linkID);
        $this->assertNotNull($liveLink);
        $this->mainSession->sendRequest('DELETE', $url, [], $headers);
        $draftLink = Versioned::get_by_stage($link::class, Versioned::DRAFT)->byID($linkID);
        $this->assertNull($draftLink);
        $liveLink = Versioned::get_by_stage($link::class, Versioned::LIVE)->byID($linkID);
        $this->assertNull($liveLink);
    }

    #[DataProvider('provideLinkSort')]
    public function testLinkSort(
        array $newLinkTextOrder,
        string $fail,
        int $expectedCode,
        array $expectedLinkTexts
    ): void {
        TestPhoneLink::$fail = $fail;
        $url = "/admin/linkfield/sort";
        $newLinkIDs = [];
        $links = TestPhoneLink::get();
        foreach ($newLinkTextOrder as $num) {
            foreach ($links as $link) {
                if ($link->LinkText === "My phone link 0$num") {
                    $newLinkIDs[] = $link->ID;
                }
            }
        }
        if ($fail === 'object-data') {
            $newLinkIDs = ['a' => 11, 'b' => 22];
        }
        $body = json_encode(['newLinkIDs' => $newLinkIDs]);
        $headers = [];
        if ($fail !== 'csrf-token') {
            $headers = array_merge($headers, $this->csrfTokenheader());
        }
        $response = $this->post($url, null, $headers, null, $body);
        $this->assertSame($expectedCode, $response->getStatusCode());
        $this->assertSame(
            $this->getExpectedLinkTexts($expectedLinkTexts),
            TestPhoneLink::get()->filter(['OwnerRelation' => 'LinkList'])->column('LinkText')
        );
    }

    public static function provideLinkSort(): array
    {
        return [
            'Success' => [
                'newLinkTextOrder' => [4, 2, 3],
                'fail' => '',
                'expectedCode' => 204,
                'expectedLinkTexts' => [4, 2, 3],
            ],
            'Emtpy data' => [
                'newLinkTextOrder' => [],
                'fail' => '',
                'expectedCode' => 400,
                'expectedLinkTexts' => [2, 3, 4],
            ],
            'Fail can edit' => [
                'newLinkTextOrder' => [4, 2, 3],
                'fail' => 'can-edit',
                'expectedCode' => 403,
                'expectedLinkTexts' => [2, 3, 4],
            ],
            'Fail object data' => [
                'newLinkTextOrder' => [],
                'fail' => 'object-data',
                'expectedCode' => 400,
                'expectedLinkTexts' => [2, 3, 4],
            ],
            'Fail csrf token' => [
                'newLinkTextOrder' => [4, 2, 3],
                'fail' => 'csrf-token',
                'expectedCode' => 400,
                'expectedLinkTexts' => [2, 3, 4],
            ],
            'Mismatched owner' => [
                'newLinkTextOrder' => [4, 1, 2],
                'fail' => '',
                'expectedCode' => 400,
                'expectedLinkTexts' => [2, 3, 4],
            ],
            'Mismatched owner relation' => [
                'newLinkTextOrder' => [4, 5, 2],
                'fail' => '',
                'expectedCode' => 400,
                'expectedLinkTexts' => [2, 3, 4],
            ],
        ];
    }

    private function getExpectedLinkTexts(array $expected): array
    {
        return array_map(function ($num) {
            return "My phone link 0$num";
        }, $expected);
    }

    private function getFixtureLink(): TestPhoneLink
    {
        return $this->objFromFixture(TestPhoneLink::class, 'TestPhoneLink01');
    }

    private function getFixtureLinkOwner(): LinkOwner
    {
        return $this->objFromFixture(LinkOwner::class, 'TestLinkOwner01');
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
