<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    public function getTitle(): string
    {
        return 'Editar Empresa';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Excluir Empresa')
                ->requiresConfirmation()
                ->modalHeading('Excluir empresa')
                ->modalDescription('Tem certeza que deseja excluir os dados da empresa? Esta ação não pode ser desfeita.')
                ->modalSubmitActionLabel('Sim, excluir')
                ->modalCancelActionLabel('Cancelar'),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Empresa atualizada')
            ->body('Os dados da empresa foram atualizados com sucesso.');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Clean CNPJ before saving
        if (!empty($data['cnpj'])) {
            $data['cnpj'] = preg_replace('/[^0-9]/', '', $data['cnpj']);
        }

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Format CNPJ for display in form
        if (!empty($data['cnpj']) && strlen($data['cnpj']) === 14) {
            $cnpj = $data['cnpj'];
            $data['cnpj'] = substr($cnpj, 0, 2) . '.' .
                substr($cnpj, 2, 3) . '.' .
                substr($cnpj, 5, 3) . '/' .
                substr($cnpj, 8, 4) . '-' .
                substr($cnpj, 12, 2);
        }

        return $data;
    }
}