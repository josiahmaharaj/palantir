<?php

namespace App\Filament\Resources\VideoLogResource\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DownloadLinkRelationManager extends RelationManager
{
    protected static string $relationship = 'downloadLinks';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('email')
            ->columns([
                TextColumn::make('email')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('expires_at')->dateTime()->sortable(),
                TextColumn::make('opened_at')->dateTime()->placeholder('Not yet'),
                TextColumn::make('downloaded_at')->dateTime()->placeholder('Not yet'),
                TextColumn::make('number_clicks')->label('Downloads'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
