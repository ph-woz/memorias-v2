<?php

namespace App\Support\Navigation;

readonly class SidebarItem
{
    public function __construct(
        public string $label,
        public string $route,
        public string $icon = 'dashboard',
        public ?string $routePattern = null,
    ) {}

    public function url(): string
    {
        return route($this->route);
    }

    public function isActive(): bool
    {
        return request()->routeIs($this->routePattern ?? $this->route);
    }
}
