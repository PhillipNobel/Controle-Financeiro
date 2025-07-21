<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use App\Models\Company;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCompanies extends ListRecords
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Cadastrar Empresa')
                ->visible(fn () => Company::count() === 0),
        ];
    }

    public function getTitle(): string
    {
        return 'Configurações da Empresa';
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    public function mount(): void
    {
        parent::mount();
        
        // If no company exists and user can create, redirect to create page
        if (Company::count() === 0 && auth()->user()->can('create', Company::class)) {
            $this->redirect(CompanyResource::getUrl('create'));
        }
        
        // If company exists, redirect to edit the first (and only) company
        if (Company::count() > 0) {
            $company = Company::first();
            if (auth()->user()->can('update', $company)) {
                $this->redirect(CompanyResource::getUrl('edit', ['record' => $company]));
            }
        }
    }
}