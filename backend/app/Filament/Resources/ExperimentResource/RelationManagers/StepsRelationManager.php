<?php

namespace App\Filament\Resources\ExperimentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/** FR-10.2: administrator manages experiment steps, including the NFR-06 fallback guidance. */
class StepsRelationManager extends RelationManager
{
    protected static string $relationship = 'steps';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('step_order')
                ->numeric()
                ->required()
                ->minValue(1)
                ->label('Order'),

            Forms\Components\TextInput::make('title')
                ->required()
                ->maxLength(120)
                ->columnSpanFull(),

            Forms\Components\Textarea::make('instruction_text')
                ->required()
                ->rows(3)
                ->columnSpanFull()
                ->label('Instruction shown to student'),

            Forms\Components\Textarea::make('preauthored_guidance')
                ->required()
                ->rows(3)
                ->columnSpanFull()
                ->helperText('NFR-06: this is shown when the Gemini service is unavailable. Must always be filled.')
                ->label('Pre-authored guidance (AI fallback)'),

            Forms\Components\TextInput::make('item_emoji')
                ->maxLength(8)
                ->label('Item emoji'),

            Forms\Components\TextInput::make('action_label')
                ->maxLength(40)
                ->label('3D action label'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('step_order')
            ->columns([
                Tables\Columns\TextColumn::make('step_order')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('item_emoji')->label(''),
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\TextColumn::make('action_label')->label('3D action'),
                Tables\Columns\IconColumn::make('preauthored_guidance')
                    ->label('Fallback')
                    ->boolean()
                    ->getStateUsing(fn ($record) => filled($record->preauthored_guidance))
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->reorderable('step_order');
    }
}
