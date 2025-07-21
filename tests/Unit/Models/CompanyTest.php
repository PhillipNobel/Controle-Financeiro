<?php

namespace Tests\Unit\Models;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_can_be_created_with_valid_data()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'cnpj' => '11.222.333/0001-81',
            'razao_social' => 'Test Company LTDA',
            'email' => 'test@company.com',
        ]);

        $this->assertInstanceOf(Company::class, $company);
        $this->assertEquals('Test Company', $company->name);
        $this->assertEquals('11.222.333/0001-81', $company->cnpj);
        $this->assertEquals('Test Company LTDA', $company->razao_social);
        $this->assertEquals('test@company.com', $company->email);
    }

    public function test_company_has_fillable_attributes()
    {
        $company = new Company();
        $fillable = $company->getFillable();

        $expectedFillable = [
            'name', 'cnpj', 'razao_social', 'inscricao_estadual',
            'telefone', 'endereco', 'email', 'pessoa_responsavel', 'website'
        ];

        foreach ($expectedFillable as $attribute) {
            $this->assertContains($attribute, $fillable);
        }
    }

    public function test_company_get_instance_returns_existing_company()
    {
        $existingCompany = Company::factory()->create(['name' => 'Existing Company']);

        $instance = Company::getInstance();

        $this->assertEquals($existingCompany->id, $instance->id);
        $this->assertEquals('Existing Company', $instance->name);
    }

    public function test_company_get_instance_creates_new_company_if_none_exists()
    {
        $this->assertEquals(0, Company::count());

        $instance = Company::getInstance();

        $this->assertEquals(1, Company::count());
        $this->assertEquals('Minha Empresa', $instance->name);
    }

    public function test_company_validate_cnpj_returns_true_for_null()
    {
        $this->assertTrue(Company::validateCnpj(null));
        $this->assertTrue(Company::validateCnpj(''));
    }

    public function test_company_validate_cnpj_returns_false_for_invalid_length()
    {
        $this->assertFalse(Company::validateCnpj('123'));
        $this->assertFalse(Company::validateCnpj('12345678901234567890'));
    }

    public function test_company_validate_cnpj_returns_true_for_valid_cnpj()
    {
        // Valid CNPJ: 11.222.333/0001-81
        $this->assertTrue(Company::validateCnpj('11.222.333/0001-81'));
        $this->assertTrue(Company::validateCnpj('11222333000181'));
    }

    public function test_company_validate_cnpj_returns_false_for_invalid_cnpj()
    {
        $this->assertFalse(Company::validateCnpj('11.222.333/0001-82'));
        $this->assertFalse(Company::validateCnpj('12345678901234'));
    }

    public function test_company_validate_cnpj_handles_formatted_and_unformatted()
    {
        $formattedCnpj = '11.222.333/0001-81';
        $unformattedCnpj = '11222333000181';

        $this->assertTrue(Company::validateCnpj($formattedCnpj));
        $this->assertTrue(Company::validateCnpj($unformattedCnpj));
    }

    public function test_company_get_formatted_cnpj_attribute_returns_null_for_empty()
    {
        $company = Company::factory()->create(['cnpj' => null]);

        $this->assertNull($company->formatted_cnpj);
    }

    public function test_company_get_formatted_cnpj_attribute_formats_valid_cnpj()
    {
        $company = Company::factory()->create(['cnpj' => '11222333000181']);

        $this->assertEquals('11.222.333/0001-81', $company->formatted_cnpj);
    }

    public function test_company_get_formatted_cnpj_attribute_returns_original_for_invalid()
    {
        $company = Company::factory()->create(['cnpj' => '123456']);

        $this->assertEquals('123456', $company->formatted_cnpj);
    }

    public function test_company_can_be_created_with_minimum_required_fields()
    {
        $company = Company::create(['name' => 'Minimal Company']);

        $this->assertEquals('Minimal Company', $company->name);
        $this->assertNull($company->cnpj);
        $this->assertNull($company->email);
    }

    public function test_company_all_fields_can_be_null_except_name()
    {
        $company = Company::create([
            'name' => 'Test Company',
            'cnpj' => null,
            'razao_social' => null,
            'inscricao_estadual' => null,
            'telefone' => null,
            'endereco' => null,
            'email' => null,
            'pessoa_responsavel' => null,
            'website' => null,
        ]);

        $this->assertEquals('Test Company', $company->name);
        $this->assertNull($company->cnpj);
        $this->assertNull($company->razao_social);
        $this->assertNull($company->inscricao_estadual);
        $this->assertNull($company->telefone);
        $this->assertNull($company->endereco);
        $this->assertNull($company->email);
        $this->assertNull($company->pessoa_responsavel);
        $this->assertNull($company->website);
    }
}