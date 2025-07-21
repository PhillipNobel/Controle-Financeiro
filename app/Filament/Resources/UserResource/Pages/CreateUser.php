<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Usuário criado com sucesso!';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Remove password_confirmation from data before saving
        unset($data['password_confirmation']);
        
        return $data;
    }
}
