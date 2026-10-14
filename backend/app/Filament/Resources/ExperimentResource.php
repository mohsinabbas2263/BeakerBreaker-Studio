<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExperimentResource\Pages;
use App\Models\Experiment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/** FR-10.2: the experiment library, maintained by the administrator. */
class ExperimentResource extends Resource
{
    protected static ?string $model = Experiment::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationGroup = 'Content';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', Str::slug((string) $state))),

            Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true),

            Forms\Components\TextInput::make('topic')->required()->maxLength(60),

            Forms\Components\Select::make('difficulty')
                ->options(['easy' => 'Easy', 'medium' => 'Medium', 'hard' => 'Hard'])
                ->required()
                ->native(false)
                ->helperText('Difficulty sets the safety tier needed: easy 1, medium 2, hard 3.'),

            Forms\Components\Select::make('grade_level')
                ->options([9 => 'Class 9', 10 => 'Class 10'])
                ->required()
                ->native(false),

            Forms\Components\Select::make('prerequisite_experiment_id')
                ->label('Prerequisite experiment')
                ->relationship('prerequisite', 'title')
                ->searchable()
                ->preload()
                ->helperText('Locked until this one is completed (FR-03.3).'),

            Forms\Components\Textarea::make('chemical_equation')
                ->required()
                ->rows(2)
                ->helperText('Shown with the experiment (FR-03.6).'),

            Forms\Components\Textarea::make('explanatory_note')->required()->rows(4),

            Forms\Components\TextInput::make('base_points')->numeric()->required()->minValue(0),
            Forms\Components\TextInput::make('estimated_minutes')->numeric()->minValue(1),
            Forms\Components\TextInput::make('emoji')->maxLength(16),
            Forms\Components\TextInput::make('card_gradient')->maxLength(160),

            Forms\Components\Toggle::make('ar_available')->label('Augmented reality available'),
            Forms\Components\Toggle::make('is_published')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('emoji')->label(''),
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('topic')->badge()->searchable(),
                Tables\Columns\TextColumn::make('difficulty')->badge()->sortable(),
                Tables\Columns\TextColumn::make('steps_count')->counts('steps')->label('Steps'),
                Tables\Columns\TextColumn::make('quiz_questions_count')->counts('quizQuestions')->label('Quiz'),
                Tables\Columns\TextColumn::make('base_points')->label('XP')->sortable(),
                Tables\Columns\IconColumn::make('ar_available')->boolean()->label('AR'),
                Tables\Columns\IconColumn::make('is_published')->boolean()->label('Live'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('difficulty')->options([
                    'easy' => 'Easy', 'medium' => 'Medium', 'hard' => 'Hard',
                ]),
                Tables\Filters\TernaryFilter::make('ar_available')->label('AR available'),
                Tables\Filters\TernaryFilter::make('is_published')->label('Published'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('grade_level');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageExperiments::route('/')];
    }
}
