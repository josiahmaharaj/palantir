<?php

namespace App\Filament\Resources;

use App\Broadcaster;
use App\Filament\Resources\VideoLogResource\Pages;
use App\Filament\Resources\VideoLogResource\RelationManagers\DownloadLinkRelationManager;
use App\Forms\Components\ChunkedFileUpload;
use App\Models\VideoLog;
use App\Status;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VideoLogResource extends Resource
{
    protected static ?string $model = VideoLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-film';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('log_id')
                    ->numeric()
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, $set, $get) {
                        $broadcaster = $get('broadcaster');
                        $formattedDate = Carbon::parse($get('due_date'))->format('jFY');

                        if ($broadcaster == Broadcaster::MTM->value) {
                            $set('title', $state.'_MTM_DaybreakAssembly_'.$formattedDate);
                        } else {
                            $set('title', $state.'_DaybreakAssembly_'.$formattedDate);
                        }
                    }),
                Forms\Components\TextInput::make('title')
                    ->live()
                    ->required(),
                Forms\Components\Select::make('broadcaster')
                    ->options(function () {
                        $options = [];
                        foreach (Broadcaster::cases() as $case) {
                            $options[$case->value] = $case->value;
                        }

                        return $options;
                    })
                    ->required(),
                Forms\Components\DatePicker::make('due_date')
                    ->required(),
                ChunkedFileUpload::make('upload_path')
                    ->label('Video')
                    ->chunkSize(10 * 1024 * 1024)
                    ->reactive()
                    ->dehydrated(false)
                    ->formatStateUsing(fn ($state, ?VideoLog $record) => $record?->videoMedia()?->file_name),
                Forms\Components\Select::make('status')
                    ->options(function () {
                        $options = [];
                        foreach (Status::cases() as $case) {
                            $options[$case->value] = $case->value;
                        }

                        return $options;
                    })
                    ->required(),
                Forms\Components\TextInput::make('related_log_id')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('due_date', 'desc')
            ->headerActions([

            ])
            ->columns([
                Tables\Columns\TextColumn::make('log_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('broadcaster')
                    ->searchable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->sortable()
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
                Tables\Filters\SelectFilter::make('broadcaster')
                    ->options([
                        'MTM' => 'MTM',
                        'TV6' => 'TV6',
                    ]),
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
            DownloadLinkRelationManager::class,
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
