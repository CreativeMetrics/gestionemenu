<?php

namespace App\Support;

class View
{
    /**
     * @param array<string, mixed> $data
     */
    public static function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = __DIR__ . '/../../views/' . $view . '.php';
        $contentFile = static function () use ($viewFile, $data) {
            extract($data, EXTR_SKIP);
            require $viewFile;
        };
        require __DIR__ . '/../../views/layout.php';
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function renderPartial(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require __DIR__ . '/../../views/' . $view . '.php';
    }
}
