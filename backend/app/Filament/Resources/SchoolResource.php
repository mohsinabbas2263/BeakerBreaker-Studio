<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SchoolResource\Pages;
use App\Models\School;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Licensed institutions: board, licensed classes and licence dates (CLAUDE.md
 * deviation 5). The project team's alone. A school administrator cannot open
 * this page, so it cannot extend its own licence or see another school.
 */
class SchoolResource extends Resource
{
    public static function canViewAny(): bool
    {
        return \App\Filament\AdminScope::isTeam();
    }

    protected static ?string $model = School::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationGroup = 'Institution';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->maxLength(160),
            Forms\Components\TextInput::make('district')->required()->maxLength(80),

            // Licensing: which board this school follows, and which classes' experiments it gets.
            Forms\Components\Select::make('board')
                ->options(\App\Models\School::BOARDS)
                ->default('punjab')
                ->required()
                ->native(false),

            Forms\Components\CheckboxList::make('licensed_grades')
                ->label('Licensed classes')
                ->options(\App\Models\School::GRADES)
                ->required()
                ->columns(2)
                ->helperText('Board exams test practicals in pairs, so a Class 9 or 10 student sees Class 9 and 10 experiments, and a Class 11 or 12 student sees 11 and 12. Only ticked classes are shown.'),
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
                Tables\Columns\TextColumn::make('board')->badge(),
                Tables\Columns\TextColumn::make('licensed_grades')->label('Classes')->badge(),
                Tables\Columns\TextColumn::make('licence_end')->date()->label('Licence ends')->sortable(),
                Tables\Columns\TextColumn::make('users_count')->counts('users')->label('Accounts'),
                Tables\Columns\TextColumn::make('student_cap')->label('Cap'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // School::deleting refuses too; hiding the button says so first.
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn (School $record) => $record->hasAccounts()),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageSchools::route('/')];
    }
}
