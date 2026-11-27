<?php

namespace App\Filament\Resources\VideoLogResource\RelationManagers;

use App\Models\VideoLogAuditLog;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AuditLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'auditLogs';

    protected static ?string $title = 'Audit log';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('event')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('event')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        VideoLogAuditLog::VideoUploaded => 'Video uploaded',
                        VideoLogAuditLog::EmailSent => 'Email sent',
                        VideoLogAuditLog::EmailFailed => 'Email failed',
                        VideoLogAuditLog::NoContacts => 'No contacts',
                        VideoLogAuditLog::LinkOpened => 'Link opened',
                        VideoLogAuditLog::FileDownloaded => 'File downloaded',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        VideoLogAuditLog::EmailSent, VideoLogAuditLog::FileDownloaded => 'success',
                        VideoLogAuditLog::LinkOpened => 'info',
                        VideoLogAuditLog::EmailFailed => 'danger',
                        VideoLogAuditLog::NoContacts => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('email')->placeholder('-'),
                Tables\Columns\TextColumn::make('message')->wrap(),
            ]);
    }
}
