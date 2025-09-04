<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LinkResource\Pages;
use App\Models\Link;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LinkResource extends Resource
{
    protected static ?string $model = Link::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('video_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('link'),
                Forms\Components\DateTimePicker::make('expired_at')
                    ->required(),
                Forms\Components\DateTimePicker::make('downloaded_at'),
                Forms\Components\TextInput::make('ip_address'),
                Forms\Components\TextInput::make('user_agent'),
                Forms\Components\TextInput::make('shortcode'),
                Forms\Components\TextInput::make('recipients'),
                Forms\Components\DateTimePicker::make('email_sent_at'),
                Forms\Components\DateTimePicker::make('file_uploaded_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('video_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('link')
                    ->searchable(),
                Tables\Columns\TextColumn::make('expired_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('downloaded_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user_agent')
                    ->searchable(),
                Tables\Columns\TextColumn::make('shortcode')
                    ->searchable(),
                Tables\Columns\TextColumn::make('recipients')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email_sent_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('file_uploaded_at')
                    ->dateTime()
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
            'index' => Pages\ListLinks::route('/'),
            'create' => Pages\CreateLink::route('/create'),
            'edit' => Pages\EditLink::route('/{record}/edit'),
        ];
    }
}
