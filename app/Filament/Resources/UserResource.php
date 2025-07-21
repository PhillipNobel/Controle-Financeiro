<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use App\Enums\UserRole;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Usuários';

    protected static ?string $modelLabel = 'Usuário';

    protected static ?string $pluralModelLabel = 'Usuários';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados do Usuário')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->required()
                            ->unique(User::class, 'email', ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\Select::make('role')
                            ->label('Função')
                            ->options([
                                UserRole::SUPER_ADMIN->value => UserRole::SUPER_ADMIN->label(),
                                UserRole::ADMIN->value => UserRole::ADMIN->label(),
                                UserRole::EDITOR->value => UserRole::EDITOR->label(),
                            ])
                            ->required()
                            ->default(UserRole::EDITOR->value),

                        Forms\Components\TextInput::make('password')
                            ->label('Senha')
                            ->password()
                            ->required(fn(string $context): bool => $context === 'create')
                            ->minLength(8)
                            ->same('password_confirmation')
                            ->dehydrated(fn($state) => filled($state))
                            ->dehydrateStateUsing(fn($state) => Hash::make($state)),

                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('Confirmar Senha')
                            ->password()
                            ->required(fn(string $context): bool => $context === 'create')
                            ->minLength(8)
                            ->dehydrated(false),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('role')
                    ->label('Função')
                    ->formatStateUsing(fn(UserRole $state): string => $state->label())
                    ->badge()
                    ->color(fn(UserRole $state): string => match ($state) {
                        UserRole::SUPER_ADMIN => 'danger',
                        UserRole::ADMIN => 'warning',
                        UserRole::EDITOR => 'success',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Função')
                    ->options([
                        UserRole::SUPER_ADMIN->value => UserRole::SUPER_ADMIN->label(),
                        UserRole::ADMIN->value => UserRole::ADMIN->label(),
                        UserRole::EDITOR->value => UserRole::EDITOR->label(),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        return $user && $user->role === UserRole::SUPER_ADMIN;
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();
        return $user && $user->role === UserRole::SUPER_ADMIN;
    }

    public static function canEdit($record): bool
    {
        $user = Auth::user();
        return $user && $user->role === UserRole::SUPER_ADMIN;
    }

    public static function canDelete($record): bool
    {
        $user = Auth::user();
        return $user &&
            $user->role === UserRole::SUPER_ADMIN &&
            $user->id !== $record->id;
    }

    public static function canDeleteAny(): bool
    {
        $user = Auth::user();
        return $user && $user->role === UserRole::SUPER_ADMIN;
    }
}
