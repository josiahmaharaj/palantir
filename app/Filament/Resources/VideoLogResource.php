<?php

namespace App\Filament\Resources;

use App\Broadcaster;
use App\Filament\Resources\VideoLogResource\Pages;
use App\Filament\Resources\VideoLogResource\RelationManagers;
use App\Models\VideoLog;
use App\Status;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VideoLogResource extends Resource
{
    protected static ?string $model = VideoLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-film';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required(),
                Forms\Components\Select::make('broadcaster')
                    ->options(function () {
                        return array_column(Broadcaster::cases(), 'value');
                    })
                    ->required(),
                Forms\Components\FileUpload::make('file'),
                Forms\Components\Select::make('status')
                    ->options(function () {
                        return array_column(Status::cases(), 'value');
                    })
                    ->required(),
                Forms\Components\TextInput::make('related_log_id')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('broadcaster')
                    ->searchable(),
                Tables\Columns\TextColumn::make('file')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('related_log_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListVideoLogs::route('/'),
            'create' => Pages\CreateVideoLog::route('/create'),
            'edit' => Pages\EditVideoLog::route('/{record}/edit'),
        ];
    }
}
