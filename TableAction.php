<?php

class TableAction
{
    protected $label;
    protected $class = 'inline-flex items-center rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-100';
    protected $url = null;

    public static function make($label = 'Action')
    {
        return new self($label);
    }

    public function __construct($label = 'Action')
    {
        $this->label = $label;
    }

    public function label($label)
    {
        $this->label = $label;
        return $this;
    }

    public function url($url)
    {
        $this->url = $url;
        return $this;
    }

    public function class($class)
    {
        $this->class = $class;
        return $this;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function render($item, $table)
    {
        $url = $this->url;

        if (is_callable($url)) {
            $url = $url($item);
        }

        if ($url === null) {
            $url = '#';
        }

        $label = $this->label;

        if (is_callable($label)) {
            $label = $label($item);
        }

        return '<a href="' . e($url) . '" class="' . e($this->class) . '">' . e((string) $label) . '</a>';
    }
}

class EditAction extends TableAction
{
    public static function make($label = 'Bewerken')
    {
        return (new self($label))->class('inline-flex items-center rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-blue-700');
    }
}

class DeleteAction extends TableAction
{
    public static function make($label = 'Verwijderen')
    {
        return (new self($label))->class('inline-flex items-center rounded-lg bg-red-600 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-red-700');
    }
}
