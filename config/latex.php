<?php

return [
    'bin' => env('LATEX_BIN', 'pdflatex'),

    'temp_dir' => env('LATEX_TEMP_DIR', storage_path('app/temp/latex')),

    'options' => env('LATEX_OPTIONS', '-interaction=nonstopmode'),
];
