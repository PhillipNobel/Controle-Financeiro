<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'cnpj',
        'razao_social',
        'inscricao_estadual',
        'telefone',
        'endereco',
        'email',
        'pessoa_responsavel',
        'website',
    ];

    /**
     * Get the company instance (singleton pattern).
     * Creates a new company if none exists.
     */
    public static function getInstance(): self
    {
        $company = self::first();

        if (!$company) {
            $company = self::create([
                'name' => 'Minha Empresa',
            ]);
        }

        return $company;
    }

    /**
     * Validate CNPJ format.
     */
    public static function validateCnpj(?string $cnpj): bool
    {
        if (empty($cnpj)) {
            return true; // CNPJ is nullable
        }

        // Remove non-numeric characters
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

        // Check if CNPJ has 14 digits
        if (strlen($cnpj) !== 14) {
            return false;
        }

        // Basic CNPJ validation algorithm
        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        // Calculate first check digit
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += intval($cnpj[$i]) * $weights1[$i];
        }
        $remainder = $sum % 11;
        $digit1 = $remainder < 2 ? 0 : 11 - $remainder;

        // Calculate second check digit
        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += intval($cnpj[$i]) * $weights2[$i];
        }
        $remainder = $sum % 11;
        $digit2 = $remainder < 2 ? 0 : 11 - $remainder;

        // Verify check digits
        return intval($cnpj[12]) === $digit1 && intval($cnpj[13]) === $digit2;
    }

    /**
     * Format CNPJ for display.
     */
    public function getFormattedCnpjAttribute(): ?string
    {
        if (empty($this->cnpj)) {
            return null;
        }

        $cnpj = preg_replace('/[^0-9]/', '', $this->cnpj);

        if (strlen($cnpj) === 14) {
            return substr($cnpj, 0, 2) . '.' .
                substr($cnpj, 2, 3) . '.' .
                substr($cnpj, 5, 3) . '/' .
                substr($cnpj, 8, 4) . '-' .
                substr($cnpj, 12, 2);
        }

        return $this->cnpj;
    }
}
