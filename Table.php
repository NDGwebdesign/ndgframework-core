<?php

class Table
{
    protected $items = [];
    protected $columns = [];
    protected $actions = [];
    protected $bulkActions = [];
    protected $searchable = [];
    protected $sortable = [];
    protected $filters = [];
    protected $perPage = 0;

    protected $containerClass = 'space-y-4';
    protected $toolbarClass = 'flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm md:flex-row md:items-end md:justify-between';
    protected $wrapperClass = 'overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm';
    protected $tableClass = 'min-w-full divide-y divide-gray-200';
    protected $theadClass = 'bg-gray-50';
    protected $thClass = 'px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500';
    protected $tbodyClass = 'divide-y divide-gray-100';
    protected $tdClass = 'px-4 py-3 text-sm text-gray-600';
    protected $rowClass = '';
    protected $emptyClass = 'px-4 py-8 text-center text-sm text-gray-500';
    protected $searchInputClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none';
    protected $filterSelectClass = 'rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm';
    protected $buttonClass = 'inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100';
    protected $paginationClass = 'flex flex-col gap-3 border-t border-gray-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between';
    protected $paginationLinkClass = 'inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100';
    protected $paginationActiveClass = 'inline-flex items-center rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white';

    public static function make($items = [])
    {
        return new self($items);
    }

    public function __construct($items = [])
    {
        $this->items = $items;
    }

    public function column($key, $label = null, array $options = [])
    {
        $this->columns[] = [
            'key' => $key,
            'label' => $label ?: $this->humanize($key),
            'options' => $options,
        ];

        return $this;
    }

    public function actions(array $actions = [])
    {
        $this->actions = $actions;

        return $this;
    }

    public function bulkActions(array $actions = [])
    {
        $this->bulkActions = $actions;

        return $this;
    }

    public function searchable($fields = null)
    {
        $this->searchable = is_array($fields) ? $fields : ($fields ? [$fields] : []);

        return $this;
    }

    public function sortable($fields = null)
    {
        $this->sortable = is_array($fields) ? $fields : ($fields ? [$fields] : []);

        return $this;
    }

    public function filters(array $filters = [])
    {
        $this->filters = $filters;

        return $this;
    }

    public function perPage($perPage)
    {
        $this->perPage = (int) $perPage;

        return $this;
    }

    public function containerClass($class)
    {
        $this->containerClass = $class;

        return $this;
    }

    public function toolbarClass($class)
    {
        $this->toolbarClass = $class;

        return $this;
    }

    public function wrapperClass($class)
    {
        $this->wrapperClass = $class;

        return $this;
    }

    public function tableClass($class)
    {
        $this->tableClass = $class;

        return $this;
    }

    public function theadClass($class)
    {
        $this->theadClass = $class;

        return $this;
    }

    public function thClass($class)
    {
        $this->thClass = $class;

        return $this;
    }

    public function tdClass($class)
    {
        $this->tdClass = $class;

        return $this;
    }

    public function rowClass($class)
    {
        $this->rowClass = $class;

        return $this;
    }

    public function searchInputClass($class)
    {
        $this->searchInputClass = $class;

        return $this;
    }

    public function filterSelectClass($class)
    {
        $this->filterSelectClass = $class;

        return $this;
    }

    public function buttonClass($class)
    {
        $this->buttonClass = $class;

        return $this;
    }

    public function paginationClass($class)
    {
        $this->paginationClass = $class;

        return $this;
    }

    public function render()
    {
        $request = $_GET;
        $search = trim((string) ($request['search'] ?? ''));
        $sort = isset($request['sort']) ? (string) $request['sort'] : '';
        $direction = isset($request['direction']) ? strtolower((string) $request['direction']) : 'asc';

        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        $page = max(1, (int) ($request['page'] ?? 1));
        $filters = $this->normalizeFilters($request);

        $items = $this->applyFilters($this->items, $search, $filters, $sort, $direction);
        $total = count($items);
        $perPage = (int) $this->perPage;
        $pages = 1;

        if ($perPage > 0) {
            $pages = max(1, (int) ceil($total / $perPage));
            $page = min($page, $pages);
            $offset = ($page - 1) * $perPage;
            $items = array_slice($items, $offset, $perPage);
        }

        $toolbar = $this->renderToolbar($search, $filters);
        $header = $this->renderHeader($search, $filters, $sort, $direction);
        $rows = $this->renderRows($items);
        $emptyState = $this->renderEmptyState();
        $pagination = $this->renderPagination($page, $pages, $total, $search, $filters, $sort, $direction);

        $html = '<div class="' . e($this->containerClass) . '">';
        $html .= $toolbar;
        $html .= '<div class="' . e($this->wrapperClass) . '">';
        $html .= '<table class="' . e($this->tableClass) . '">';
        $html .= '<thead class="' . e($this->theadClass) . '">';
        $html .= $header;
        $html .= '</thead>';
        $html .= '<tbody class="' . e($this->tbodyClass) . '">';
        $html .= $rows ?: $emptyState;
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= $pagination;
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function __toString()
    {
        return $this->render();
    }

    protected function renderToolbar($search, $filters)
    {
        $hasSearch = !empty($this->searchable);
        $hasFilters = !empty($this->filters);
        $hasBulkActions = !empty($this->bulkActions);

        if (!$hasSearch && !$hasFilters && !$hasBulkActions) {
            return '';
        }

        $html = '<div class="' . e($this->toolbarClass) . '">';

        if ($hasSearch || $hasFilters) {
            $html .= '<form method="get" class="flex flex-1 flex-col gap-3 md:flex-row md:items-end">';

            if ($hasSearch) {
                $html .= '<div class="flex-1">';
                $html .= '<label class="mb-1 block text-sm font-medium text-gray-700">Zoeken</label>';
                $html .= '<input type="text" name="search" value="' . e($search) . '" placeholder="Zoek in de tabel..." class="' . e($this->searchInputClass) . '">';
                $html .= '</div>';
            }

            foreach ($this->filters as $filter) {
                $name = 'filter_' . $filter['key'];
                $value = $filters[$filter['key']] ?? '';
                $html .= '<div>';
                $html .= '<label class="mb-1 block text-sm font-medium text-gray-700">' . e($filter['label'] ?? $this->humanize($filter['key'])) . '</label>';
                $html .= '<select name="' . e($name) . '" class="' . e($this->filterSelectClass) . '">';
                $html .= '<option value="">Alle</option>';

                foreach ($filter['options'] ?? [] as $optionValue => $optionLabel) {
                    $selected = $value === (string) $optionValue ? ' selected' : '';
                    $html .= '<option value="' . e($optionValue) . '"' . $selected . '>' . e($optionLabel) . '</option>';
                }

                $html .= '</select>';
                $html .= '</div>';
            }

            $html .= '<div class="flex items-center gap-2">';
            $html .= '<button type="submit" class="' . e($this->buttonClass) . '">Toepassen</button>';

            if ($this->hasActiveFilters($search, $filters)) {
                $html .= '<a href="' . e($this->currentUrl()) . '" class="' . e($this->buttonClass) . '">Reset</a>';
            }

            $html .= '</div>';
            $html .= '</form>';
        }

        if ($hasBulkActions) {
            $html .= '<form method="post" class="flex items-center gap-2">';
            $html .= '<select class="' . e($this->filterSelectClass) . '" name="bulk_action">';

            foreach ($this->bulkActions as $action) {
                $label = $action instanceof TableAction ? $action->getLabel() : (string) $action;
                $html .= '<option value="' . e($label) . '">' . e($label) . '</option>';
            }

            $html .= '</select>';
            $html .= '<button type="submit" class="' . e($this->buttonClass) . '">Uitvoeren</button>';
            $html .= '</form>';
        }

        $html .= '</div>';

        return $html;
    }

    protected function renderHeader($search, $filters, $sort, $direction)
    {
        $html = '<tr>';

        foreach ($this->columns as $column) {
            $key = $column['key'];
            $label = $column['label'];
            $html .= '<th class="' . e($this->thClass) . '">';

            if ($this->isSortableColumn($column)) {
                $newDirection = ($sort === $key && $direction === 'asc') ? 'desc' : 'asc';
                $html .= '<a href="' . e($this->buildUrl($search, $filters, $key, $newDirection, 1)) . '" class="flex items-center gap-1 text-gray-700 hover:text-gray-900">';
                $html .= e($label);
                $html .= $this->renderSortIcon($key, $sort, $direction);
                $html .= '</a>';
            } else {
                $html .= e($label);
            }

            $html .= '</th>';
        }

        if (!empty($this->actions)) {
            $html .= '<th class="' . e($this->thClass) . '">Acties</th>';
        }

        $html .= '</tr>';

        return $html;
    }

    protected function renderRows($items)
    {
        if (empty($items)) {
            return '';
        }

        $html = '';

        foreach ($items as $item) {
            $html .= '<tr class="' . e($this->rowClass) . '">';

            foreach ($this->columns as $column) {
                $value = $this->formatCellValue($item, $column);
                $cellClass = $this->tdClass;

                if (!empty($column['options']['class'])) {
                    $cellClass .= ' ' . $column['options']['class'];
                }

                $html .= '<td class="' . e($cellClass) . '">';
                $html .= $value;
                $html .= '</td>';
            }

            if (!empty($this->actions)) {
                $html .= '<td class="' . e($this->tdClass) . '">';
                $html .= '<div class="flex flex-wrap gap-2">';

                foreach ($this->actions as $action) {
                    $html .= $action instanceof TableAction ? $action->render($item, $this) : '';
                }

                $html .= '</div>';
                $html .= '</td>';
            }

            $html .= '</tr>';
        }

        return $html;
    }

    protected function renderEmptyState()
    {
        $html = '<tr>';
        $colspan = count($this->columns) + (!empty($this->actions) ? 1 : 0);
        $html .= '<td colspan="' . $colspan . '" class="' . e($this->emptyClass) . '">';
        $html .= 'Geen resultaten gevonden.';
        $html .= '</td>';
        $html .= '</tr>';

        return $html;
    }

    protected function renderPagination($page, $pages, $total, $search, $filters, $sort, $direction)
    {
        if ($pages <= 1) {
            return '';
        }

        $html = '<div class="' . e($this->paginationClass) . '">';
        $html .= '<p class="text-sm text-gray-500">Tonen ' . e($total ? 1 : 0) . ' tot ' . e(min($page * $this->perPage, $total)) . ' van ' . e($total) . ' resultaten</p>';
        $html .= '<div class="flex flex-wrap items-center gap-2">';

        if ($page > 1) {
            $html .= '<a href="' . e($this->buildUrl($search, $filters, $sort, $direction, $page - 1)) . '" class="' . e($this->paginationLinkClass) . '">Vorige</a>';
        }

        for ($i = 1; $i <= $pages; $i++) {
            $activeClass = $i === $page ? $this->paginationActiveClass : $this->paginationLinkClass;
            $html .= '<a href="' . e($this->buildUrl($search, $filters, $sort, $direction, $i)) . '" class="' . e($activeClass) . '">' . e($i) . '</a>';
        }

        if ($page < $pages) {
            $html .= '<a href="' . e($this->buildUrl($search, $filters, $sort, $direction, $page + 1)) . '" class="' . e($this->paginationLinkClass) . '">Volgende</a>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    protected function applyFilters($items, $search, $filters, $sort, $direction)
    {
        $filtered = [];
        $searchValue = strtolower($search);

        foreach ($items as $item) {
            $matchesSearch = true;

            if ($searchValue !== '' && !empty($this->searchable)) {
                $matchesSearch = false;
                $searchableFields = $this->searchable;

                foreach ($searchableFields as $field) {
                    $value = strtolower((string) $this->getValue($item, $field));
                    if (str_contains($value, $searchValue)) {
                        $matchesSearch = true;
                        break;
                    }
                }
            }

            if (!$matchesSearch) {
                continue;
            }

            $matchesFilters = true;

            foreach ($filters as $filterKey => $filterValue) {
                if ($filterValue === '') {
                    continue;
                }

                $itemValue = (string) $this->getValue($item, $filterKey);
                if (strtolower($itemValue) !== strtolower((string) $filterValue)) {
                    $matchesFilters = false;
                    break;
                }
            }

            if ($matchesFilters) {
                $filtered[] = $item;
            }
        }

        if ($sort !== '') {
            usort($filtered, function ($left, $right) use ($sort, $direction) {
                $leftValue = $this->getValue($left, $sort);
                $rightValue = $this->getValue($right, $sort);
                $comparison = strnatcasecmp((string) $leftValue, (string) $rightValue);

                if ($comparison === 0) {
                    return 0;
                }

                return $direction === 'desc' ? ($comparison > 0 ? -1 : 1) : ($comparison > 0 ? 1 : -1);
            });
        }

        return $filtered;
    }

    protected function normalizeFilters($request)
    {
        $filters = [];

        foreach ($this->filters as $filter) {
            $key = $filter['key'];
            $value = isset($request['filter_' . $key]) ? (string) $request['filter_' . $key] : '';
            $filters[$key] = $value;
        }

        return $filters;
    }

    protected function formatCellValue($item, $column)
    {
        $value = $this->getValue($item, $column['key']);

        if (!empty($column['options']['format']) && is_callable($column['options']['format'])) {
            return call_user_func($column['options']['format'], $value, $item);
        }

        if (is_array($value) || is_object($value)) {
            return e(json_encode($value));
        }

        return e((string) $value);
    }

    protected function getValue($item, $key)
    {
        if (is_array($item)) {
            if (array_key_exists($key, $item)) {
                return $item[$key];
            }

            if (strpos($key, '.') !== false) {
                $segments = explode('.', $key);
                $value = $item;

                foreach ($segments as $segment) {
                    if (is_array($value) && array_key_exists($segment, $value)) {
                        $value = $value[$segment];
                    } else {
                        return '';
                    }
                }

                return $value;
            }

            return '';
        }

        if (is_object($item)) {
            if (property_exists($item, $key)) {
                return $item->$key;
            }

            if (method_exists($item, 'get' . ucfirst($key))) {
                return $item->{'get' . ucfirst($key)}();
            }

            return '';
        }

        return '';
    }

    protected function isSortableColumn($column)
    {
        $key = $column['key'];

        if (!empty($column['options']['sortable'])) {
            return (bool) $column['options']['sortable'];
        }

        if (!empty($this->sortable)) {
            return in_array($key, $this->sortable, true);
        }

        return true;
    }

    protected function renderSortIcon($key, $sort, $direction)
    {
        if ($sort !== $key) {
            return '<span class="text-gray-400">⇅</span>';
        }

        return $direction === 'asc' ? '<span class="text-blue-600">↑</span>' : '<span class="text-blue-600">↓</span>';
    }

    protected function buildUrl($search, $filters, $sort, $direction, $page)
    {
        $params = $_GET;
        unset($params['search'], $params['sort'], $params['direction'], $params['page']);

        foreach ($this->filters as $filter) {
            unset($params['filter_' . $filter['key']]);
        }

        if ($search !== '') {
            $params['search'] = $search;
        }

        if ($sort !== '') {
            $params['sort'] = $sort;
        }

        if ($direction !== 'asc') {
            $params['direction'] = $direction;
        }

        if ($page > 1) {
            $params['page'] = $page;
        }

        $path = strtok((string) ($_SERVER['REQUEST_URI'] ?? '/'), '?');
        $query = http_build_query($params);

        return $path . ($query !== '' ? '?' . $query : '');
    }

    protected function currentUrl()
    {
        $path = strtok((string) ($_SERVER['REQUEST_URI'] ?? '/'), '?');
        $query = http_build_query($_GET);

        return $path . ($query !== '' ? '?' . $query : '');
    }

    protected function hasActiveFilters($search, $filters)
    {
        if ($search !== '') {
            return true;
        }

        foreach ($filters as $value) {
            if ($value !== '') {
                return true;
            }
        }

        return false;
    }

    protected function humanize($value)
    {
        $value = str_replace(['_', '-'], ' ', (string) $value);
        $value = ucwords($value);

        return trim($value);
    }
}
