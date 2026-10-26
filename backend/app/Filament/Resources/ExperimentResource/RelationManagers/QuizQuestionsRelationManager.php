<?php

namespace App\Filament\Resources\ExperimentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/** FR-10.2: administrator manages quiz questions. FR-02.5: correct_option is stored here, never sent to clients. */
class QuizQuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'quizQuestions';

    protected static ?string $title = 'Quiz Questions';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Textarea::make('prompt')
                ->required()
                ->rows(3)
                ->columnSpanFull()
                ->label('Question prompt'),

            Forms\Components\Repeater::make('options')
                ->simple(Forms\Components\TextInput::make()->required())
                ->minItems(2)
                ->maxItems(4)
                ->reorderable()
                ->columnSpanFull()
                ->label('Answer options (drag to reorder — update Correct option index to match)')
                ->helperText('Options are stored as a JSON array. Index 0 = first option, 1 = second, etc.'),

            Forms\Components\Select::make('correct_option')
                ->options([0 => 'Option 1 (index 0)', 1 => 'Option 2 (index 1)', 2 => 'Option 3 (index 2)', 3 => 'Option 4 (index 3)'])
                ->required()
                ->native(false)
                ->helperText('FR-02.5: the answer key — never sent to the student, evaluated on the server.'),

            Forms\Components\Textarea::make('explanation')
                ->rows(2)
                ->columnSpanFull()
                ->label('Explanation (shown on wrong answer)'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('prompt')
            ->columns([
                Tables\Columns\TextColumn::make('prompt')
                    ->limit(80)
                    ->searchable(),
                Tables\Columns\TextColumn::make('correct_option')
                    ->label('Correct')
                    ->formatStateUsing(fn ($state) => 'Option '.($state + 1)),
                Tables\Columns\IconColumn::make('explanation')
                    ->label('Expl.')
                    ->boolean()
                    ->getStateUsing(fn ($record) => filled($record->explanation))
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->trueColor('success')
                    ->falseColor('warning'),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
