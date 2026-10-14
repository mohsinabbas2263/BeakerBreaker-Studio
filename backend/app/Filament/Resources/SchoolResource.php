<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SchoolResource\Pages;
use App\Models\School;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/** FR-10.5: licensed institutions and their caps. */
class SchoolResource extends Resource
{
    protected static ?string $model = School::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationGroup = 'Institution';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->maxLength(160),
            Forms\Components\TextInput::make('district')->required()->maxLength(80),
            Forms\Components\DatePicker::make('licence_start')->required(),
            Forms\Components\DatePicker::make('licence_end')->required()->after('licence_start'),
            Forms\Components\TextInput::make('student_cap')->numeric()->minValue(0)->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('district')->searchable(),
                Tables\Columns\TextColumn::make('licence_end')->date()->label('Licence ends')->sortable(),
                Tables\Columns\TextColumn::make('users_count')->counts('users')->label('Accounts'),
                Tables\Columns\TextColumn::make('student_cap')->label('Cap'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageSchools::route('/')];
    }
}
