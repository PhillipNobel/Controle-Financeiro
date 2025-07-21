<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Enums\UserRole;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()?->role === UserRole::SUPER_ADMIN && 
                                 auth()->user()?->id !== $this->record->id),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Usuário atualizado com sucesso!';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Remove password_confirmation from data before saving
        unset($data['password_confirmation']);
        
        // If password is empty, don't update it
        if (empty($data['password'])) {
            unset($data['password']);
        }
        
        return $data;
    }
}
