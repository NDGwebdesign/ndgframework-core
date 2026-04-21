<?php

class Controller
{
    protected function view($view, $data = [])
    {
        return new View($view, $data);
    }
}