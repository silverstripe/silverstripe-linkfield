<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Tests\Controllers\LinkFieldControllerTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Forms\Validation\CompositeValidator;
use SilverStripe\Forms\Validation\Validator;
use SilverStripe\LinkField\Models\Link;

class TestPhoneLink extends Link implements TestOnly
{
    private static string $table_name = 'LinkField_TestPhoneLink';

    private static array $db = [
        'Phone' => 'Varchar',
    ];

    public static $fail = '';

    public function getDescription(): string
    {
        return $this->Phone ?: '';
    }

    public function canView($member = null)
    {
        return TestPhoneLink::$fail !== 'can-view';
    }

    public function canCreate($member = null, $context = [])
    {
        return TestPhoneLink::$fail !== 'can-create';
    }

    public function canEdit($member = null, $context = [])
    {
        return TestPhoneLink::$fail !== 'can-edit';
    }

    public function canDelete($member = null)
    {
        return TestPhoneLink::$fail !== 'can-delete';
    }

    public function validate(): ValidationResult
    {
        $validationResult = parent::validate();
        if (TestPhoneLink::$fail === 'validate') {
            $validationResult->addFieldError('Fail', 'Fail was validate');
        }
        return $validationResult;
    }

    public function getCMSCompositeValidator(): CompositeValidator
    {
        $compositeValidator = parent::getCMSCompositeValidator();
        $compositeValidator->addValidator(new class extends Validator {
            public function php($data): bool
            {
                $valid = true;
                if (TestPhoneLink::$fail == 'cms-composite-validator') {
                    $valid = false;
                    $this->validationError('Fail', 'Fail was cms-composite-validator');
                }
                return $valid;
            }
        });
        return $compositeValidator;
    }
}
