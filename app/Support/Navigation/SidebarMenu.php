<?php

namespace App\Support\Navigation;

class SidebarMenu
{
    /**
     * @return list<SidebarItem>
     */
    public function items(): array
    {
        return [
            new SidebarItem(
                label: 'Dashboard',
                route: 'dashboard',
                icon: 'dashboard',
            ),
            new SidebarItem(
                label: 'Artigos',
                route: 'artigos.index',
                icon: 'articles',
                routePattern: 'artigos.*',
            ),
            new SidebarItem(
                label: 'Categorias',
                route: 'categorias.index',
                icon: 'categories',
                routePattern: 'categorias.*',
            ),
            new SidebarItem(
                label: 'Palavras-chave',
                route: 'palavras_chave.index',
                icon: 'keywords',
                routePattern: 'palavras_chave.*',
            ),
            new SidebarItem(
                label: 'Áreas temáticas',
                route: 'areas_tematicas.index',
                icon: 'categories',
                routePattern: 'areas_tematicas.*',
            ),
            new SidebarItem(
                label: 'Etiquetas',
                route: 'etiquetas.index',
                icon: 'keywords',
                routePattern: 'etiquetas.*',
            ),
            new SidebarItem(
                label: 'Banners',
                route: 'banners.index',
                icon: 'banners',
                routePattern: 'banners.*',
            ),
            new SidebarItem(
                label: 'Menu',
                route: 'menu.index',
                icon: 'menu',
                routePattern: 'menu.*',
            ),
            new SidebarItem(
                label: 'Páginas',
                route: 'paginas.index',
                icon: 'pages',
                routePattern: 'paginas.*',
            ),
            new SidebarItem(
                label: 'Usuários',
                route: 'usuarios.index',
                icon: 'users',
                routePattern: 'usuarios.*',
            ),
        ];
    }
}
