<?php

namespace App\Filament\Resources;

use App\Broadcaster;
use App\Filament\Resources\VideoLogResource\Pages\CreateVideoLog;
use App\Filament\Resources\VideoLogResource\Pages\EditVideoLog;
use App\Filament\Resources\VideoLogResource\Pages\ListVideoLogs;
use App\Filament\Resources\VideoLogResource\RelationManagers\AuditLogsRelationManager;
use App\Filament\Resources\VideoLogResource\RelationManagers\DownloadLinkRelationManager;
use App\Forms\Components\ChunkedFileUpload;
use App\Models\VideoLog;
use App\Status;
use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VideoLogResource extends Resource
{
    protected static ?string $model = VideoLog::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-film';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('log_id')
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
                TextInput::make('title')
                    ->live()
                    ->required(),
                Select::make('broadcaster')
                    ->options(function () {
                        $options = [];
                        foreach (Broadcaster::cases() as $case) {
                            $options[$case->value] = $case->value;
                        }

                        return $options;
                    })
                    ->required(),
                DatePicker::make('due_date')
                    ->required(),
                ChunkedFileUpload::make('upload_path')
                    ->label('Video')
                    ->visibleOn('edit')
                    ->chunkSize(10 * 1024 * 1024)
                    ->reactive()
                    ->dehydrated(false)
                    ->formatStateUsing(fn ($state, ?VideoLog $record) => $record?->videoMedia()?->file_name),
                Select::make('status')
                    ->options(function () {
                        $options = [];
                        foreach (Status::cases() as $case) {
                            $options[$case->value] = $case->value;
                        }

                        return $options;
                    })
                    ->required(),
                TextInput::make('related_log_id')
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
                TextColumn::make('log_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('title')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('broadcaster')
                    ->searchable(),
                TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('status')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('related_log_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('broadcaster')
                    ->options([
                        'MTM' => 'MTM',
                        'TV6' => 'TV6',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AuditLogsRelationManager::class,
            DownloadLinkRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVideoLogs::route('/'),
            'create' => CreateVideoLog::route('/create'),
            'edit' => EditVideoLog::route('/{record}/edit'),
        ];
    }
}
