<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use App\Models\Company;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateCompany extends CreateRecord
{
    protected static string $resource = CompanyResource::class;

    public function getTitle(): string
    {
        return 'Cadastrar Empresa';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Empresa cadastrada')
            ->body('Os dados da empresa foram cadastrados com sucesso.');
    }

    public function mount(): void
    {
        parent::mount();
        
        // Prevent creation if company already exists
        if (Company::count() > 0) {
            $company = Company::first();
            $this->redirect(CompanyResource::getUrl('edit', ['record' => $company]));
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Clean CNPJ before saving
        if (!empty($data['cnpj'])) {
            $data['cnpj'] = preg_replace('/[^0-9]/', '', $data['cnpj']);
        }

        return $data;
    }
}