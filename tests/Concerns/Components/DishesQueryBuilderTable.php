<?php

namespace PowerComponents\LivewirePowerGrid\Tests\Concerns\Components;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use NumberFormatter;
use PowerComponents\LivewirePowerGrid\{Button,
    Column,
    Components\SetUp\Exportable,
    Facades\PowerGrid,
    PowerGridComponent,
    PowerGridFields};

class DishesQueryBuilderTable extends PowerGridComponent
{
    public string $tableName = 'testing-dishes-query-builder-table';

    public string $primaryKey = 'dishes.id';

    public array $testFilters = [];

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::exportable('export')
                ->striped()
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),

            PowerGrid::header()
                ->showToggleColumns()
                ->showSearchInput(),

            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return DB::table('dishes')
            ->join('categories', function ($categories) {
                $categories->on('dishes.category_id', '=', 'categories.id');
            })
            ->select('dishes.*', 'categories.name as category_name');
    }

    public function relationSearch(): array
    {
        return [
            'category' => [
                'name',
            ],
        ];
    }

    public function fields(): PowerGridFields
    {
        $fmt = new NumberFormatter('ca_ES', NumberFormatter::CURRENCY);

        return PowerGrid::fields()
            ->add('id')
            ->add('name')
            ->add('storage_room')
            ->add('chef_name')
            ->add('serving_at')
            ->add('calories')
            ->add('calories', function ($dish) {
                return $dish->calories . ' kcal';
            })
            ->add('category_id', function ($dish) {
                return $dish->category_id;
            })
            ->add('category_name')
            ->add('price')
            ->add('price_EUR', function ($dish) use ($fmt) {
                return $fmt->formatCurrency($dish->price, 'EUR');
            })
            ->add('price_BRL', function ($dish) {
                return 'R$ ' . number_format($dish->price, 2, ',', '.'); //R$ 1.000,00
            })
            ->add('sales_price')
            ->add('sales_price_BRL', function ($dish) {
                $sales_price = $dish->price + ($dish->price * 0.15);

                return 'R$ ' . number_format($sales_price, 2, ',', '.'); //R$ 1.000,00
            })
            ->add('in_stock')
            ->add('in_stock_label', function ($dish) {
                return ($dish->in_stock ? 'sim' : 'não');
            })
            ->add('produced_at')
            ->add('produced_at_formatted', function ($dish) {
                return Carbon::parse($dish->produced_at)->format('d/m/Y');
            });
    }

    public function columns(): array
    {
        return [
            Column::add()
                ->title('ID')
                ->field('id')
                ->searchable()
                ->sortable(),

            Column::add()
                ->title('Stored at')
                ->field('storage_room')
                ->sortable(),

            Column::add()
                ->title('Dish')
                ->field('name')
                ->searchable()
                ->placeholder('Prato placeholder')
                ->sortable(),

            Column::add()
                ->title('Serving at')
                ->field('serving_at')
                ->sortable(),

            Column::add()
                ->title('Chef')
                ->field('chef_name')
                ->searchable()
                ->placeholder('Chef placeholder')
                ->sortable(),

            Column::add()
                ->title('Category')
                ->field('category_name')
                ->placeholder('Category placeholder'),

            Column::add()
                ->title('Price')
                ->field('price_BRL'),

            Column::add()
                ->title('Sales Price')
                ->field('sales_price_BRL'),

            Column::add()
                ->title('Calories')
                ->field('calories')
                ->sortable(),

            Column::add()
                ->title('In Stock')
                ->toggleable(true, 'sim', 'não')
                ->field('in_stock'),

            Column::add()
                ->title('Produced At')
                ->field('produced_at_formatted'),

            Column::add()
                ->title(__('Data'))
                ->field('produced_at')
                ->sortable(),

            Column::action('Action'),
        ];
    }

    public function actions($row): array
    {
        return [
            Button::add('edit-stock')
                ->slot('<div id="edit">Edit</div>')
                ->class('text-center')
                ->openModal('edit-stock', ['dishId' => $row->id]),

            Button::add('destroy')
                ->slot(__('Delete'))
                ->class('text-center')
                ->dispatch('deletedEvent', ['dishId' => $row->id]),
        ];
    }

    public function filters(): array
    {
        return $this->testFilters;
    }

    public function setTestThemeClass(string $themeClass): void
    {
        config(['livewire-powergrid.theme' => $themeClass]);
    }
}
