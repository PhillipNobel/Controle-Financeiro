<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'cnpj' => $this->generateValidCnpj(),
            'razao_social' => fake()->company() . ' LTDA',
            'inscricao_estadual' => fake()->numerify('###.###.###.###'),
            'telefone' => fake()->phoneNumber(),
            'endereco' => fake()->address(),
            'email' => fake()->companyEmail(),
            'pessoa_responsavel' => fake()->name(),
            'website' => fake()->url(),
        ];
    }

    /**
     * Generate a valid CNPJ for testing.
     */
    private function generateValidCnpj(): string
    {
        // Generate first 12 digits
        $cnpj = '';
        for ($i = 0; $i < 12; $i++) {
            $cnpj .= fake()->numberBetween(0, 9);
        }

        // Calculate check digits
        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        // First check digit
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += intval($cnpj[$i]) * $weights1[$i];
        }
        $remainder = $sum % 11;
        $digit1 = $remainder < 2 ? 0 : 11 - $remainder;

        // Second check digit
        $cnpjWithFirstDigit = $cnpj . $digit1;
        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += intval($cnpjWithFirstDigit[$i]) * $weights2[$i];
        }
        $remainder = $sum % 11;
        $digit2 = $remainder < 2 ? 0 : 11 - $remainder;

        $fullCnpj = $cnpj . $digit1 . $digit2;

        // Format CNPJ
        return substr($fullCnpj, 0, 2) . '.' .
               substr($fullCnpj, 2, 3) . '.' .
               substr($fullCnpj, 5, 3) . '/' .
               substr($fullCnpj, 8, 4) . '-' .
               substr($fullCnpj, 12, 2);
    }
}
