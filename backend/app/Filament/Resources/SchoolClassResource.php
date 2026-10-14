<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SchoolClassResource\Pages;
use App\Models\SchoolClass;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * FR-10.1: classes, and the teachers assigned to them.
 * FR-09.1 depends on this assignment: a teacher reaches a student only through it.
 */
class SchoolClassResource extends Resource
{
    protected static ?string $model = SchoolClass::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Institution';

    protected static ?string $modelLabel = 'class';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('school_id')
                ->relationship('school', 'name')
                ->required()
                ->searchable()
                ->preload(),

            Forms\Components\TextInput::make('title')->required()->maxLength(60),

            Forms\Components\Select::make('grade_level')
                ->options([9 => 'Class 9', 10 => 'Class 10'])
                ->required()
                ->native(false),

            Forms\Components\TextInput::make('academic_year')
                ->required()
                ->placeholder('2026-2027')
                ->maxLength(9),

            Forms\Components\Select::make('teachers')
                ->relationship('teachers', 'display_name')
                ->multiple()
                ->preload()
                ->searchable()
                ->helperText('Only an assigned teacher may act on this class (FR-09.1).'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('school.name')->label('School')->searchable(),
                Tables\Columns\TextColumn::make('grade_level')->label('Grade')->sortable(),
                Tables\Columns\TextColumn::make('academic_year')->label('Year'),
                Tables\Columns\TextColumn::make('student_profiles_count')->counts('studentProfiles')->label('Students'),
                Tables\Columns\TextColumn::make('teachers.display_name')->label('Teachers')->badge(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageSchoolClasses::route('/')];
    }
}
