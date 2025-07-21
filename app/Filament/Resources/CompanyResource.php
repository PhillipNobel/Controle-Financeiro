<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Illuminate\Support\Facades\Auth;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Empresa';

    protected static ?string $modelLabel = 'Empresa';

    protected static ?string $pluralModelLabel = 'Empresa';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informações Básicas')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nome da Empresa')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Digite o nome da empresa'),

                                TextInput::make('razao_social')
                                    ->label('Razão Social')
                                    ->maxLength(255)
                                    ->placeholder('Digite a razão social'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('cnpj')
                                    ->label('CNPJ')
                                    ->mask('99.999.999/9999-99')
                                    ->placeholder('00.000.000/0000-00')
                                    ->rules([
                                        function () {
                                            return function (string $attribute, $value, \Closure $fail) {
                                                if (!empty($value) && !Company::validateCnpj($value)) {
                                                    $fail('O CNPJ informado não é válido.');
                                                }
                                            };
                                        },
                                    ])
                                    ->helperText('Formato: 00.000.000/0000-00'),

                                TextInput::make('inscricao_estadual')
                                    ->label('Inscrição Estadual')
                                    ->maxLength(50)
                                    ->placeholder('Digite a inscrição estadual'),
                            ]),
                    ]),

                Section::make('Informações de Contato')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('telefone')
                                    ->label('Telefone')
                                    ->tel()
                                    ->maxLength(20)
                                    ->placeholder('(00) 0000-0000'),

                                TextInput::make('email')
                                    ->label('E-mail')
                                    ->email()
                                    ->maxLength(255)
                                    ->placeholder('contato@empresa.com.br'),
                            ]),

                        TextInput::make('website')
                            ->label('Website')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('https://www.empresa.com.br')
                            ->prefix('https://'),
                    ]),

                Section::make('Endereço e Responsável')
                    ->schema([
                        Textarea::make('endereco')
                            ->label('Endereço Completo')
                            ->rows(3)
                            ->placeholder('Digite o endereço completo da empresa'),

                        TextInput::make('pessoa_responsavel')
                            ->label('Pessoa Responsável')
                            ->maxLength(255)
                            ->placeholder('Nome do responsável pela empresa'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome da Empresa')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('cnpj')
                    ->label('CNPJ')
                    ->formatStateUsing(fn($state) => $state ?
                        (new Company(['cnpj' => $state]))->formatted_cnpj : '-')
                    ->searchable(),

                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('E-mail copiado!')
                    ->placeholder('-'),

                TextColumn::make('telefone')
                    ->label('Telefone')
                    ->placeholder('-'),

                TextColumn::make('pessoa_responsavel')
                    ->label('Responsável')
                    ->placeholder('-')
                    ->limit(30),

                TextColumn::make('updated_at')
                    ->label('Última Atualização')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
            ])
            ->bulkActions([
                // Não permitir ações em massa para empresa (singleton)
            ])
            ->emptyStateHeading('Nenhuma empresa cadastrada')
            ->emptyStateDescription('Configure os dados da sua empresa para começar.')
            ->emptyStateIcon('heroicon-o-building-office');
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
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        // Only allow creation if no company exists (singleton pattern)
        if (Company::count() > 0) {
            return false;
        }

        // Check if user is authenticated and has permission
        if (!Auth::check()) {
            return false;
        }

        $user = Auth::user();
        if (!$user || !method_exists($user, 'can')) {
            return false;
        }

        return $user->can('create', Company::class);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::count();
        return $count > 0 ? '1' : '0';
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::count();
        return $count > 0 ? 'success' : 'warning';
    }
}
