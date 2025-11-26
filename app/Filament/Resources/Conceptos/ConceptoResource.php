<?php

namespace App\Filament\Resources\Conceptos;

use App\Filament\Resources\Conceptos\Pages\CreateConcepto;
use App\Filament\Resources\Conceptos\Pages\EditConcepto;
use App\Filament\Resources\Conceptos\Pages\ListConceptos;
use App\Filament\Resources\Conceptos\Schemas\ConceptoForm;
use App\Filament\Resources\Conceptos\Tables\ConceptosTable;
use App\Models\Concepto;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ConceptoResource extends Resource
{
    protected static ?string $model = Concepto::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'codigo';
    
    protected static string|\UnitEnum|null $navigationGroup = 'Administración';
    protected static ?string $breadcrumb = 'Conceptos';

    public static function form(Schema $schema): Schema
    {
        return ConceptoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ConceptosTable::configure($table);
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
            'index' => ListConceptos::route('/'),
            'create' => CreateConcepto::route('/create'),
            'edit' => EditConcepto::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
