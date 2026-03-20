<?php

namespace Tests\Unit\Core;

use Gametech\Core\Tree;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class TreeActiveStateTest extends TestCase
{
    public function test_parent_menu_is_active_and_open_when_child_key_is_current(): void
    {
        $tree = $this->makeTree('/admin/lotto/groups', 'lotto.groups');

        $parent = [
            'key' => 'lotto',
            'url' => '/admin/lotto/groups',
            'children' => [
                [
                    'key' => 'lotto.groups',
                    'url' => '/admin/lotto/groups',
                    'children' => [],
                ],
                [
                    'key' => 'lotto.markets',
                    'url' => '/admin/lotto/markets',
                    'children' => [],
                ],
            ],
        ];

        $child = $parent['children'][0];

        $this->assertSame('active', $tree->getActive($parent));
        $this->assertSame('menu-open', $tree->getActives($parent));
        $this->assertSame('active', $tree->getActive($child));
        $this->assertNull($tree->getActives($child));
    }

    public function test_parent_menu_is_still_open_when_current_path_is_nested_under_child_page(): void
    {
        $tree = $this->makeTree('/admin/lotto/groups/123', null);

        $parent = [
            'key' => 'lotto',
            'url' => '/admin/lotto/groups',
            'children' => [
                [
                    'key' => 'lotto.groups',
                    'url' => '/admin/lotto/groups',
                    'children' => [],
                ],
                [
                    'key' => 'lotto.markets',
                    'url' => '/admin/lotto/markets',
                    'children' => [],
                ],
            ],
        ];

        $child = $parent['children'][0];
        $sibling = $parent['children'][1];

        $this->assertSame('active', $tree->getActive($parent));
        $this->assertSame('menu-open', $tree->getActives($parent));
        $this->assertSame('active', $tree->getActive($child));
        $this->assertNull($tree->getActive($sibling));
    }

    public function test_unrelated_parent_menu_is_not_opened(): void
    {
        $tree = $this->makeTree('/admin/lotto/markets', 'lotto.markets');

        $wallet = [
            'key' => 'wallet',
            'url' => '/admin/member',
            'children' => [
                [
                    'key' => 'wallet.member',
                    'url' => '/admin/member',
                    'children' => [],
                ],
            ],
        ];

        $this->assertNull($tree->getActive($wallet));
        $this->assertNull($tree->getActives($wallet));
    }

    private function makeTree(string $currentPath, ?string $currentKey): Tree
    {
        $reflection = new ReflectionClass(Tree::class);
        /** @var Tree $tree */
        $tree = $reflection->newInstanceWithoutConstructor();
        $tree->items = [];
        $tree->roles = [];
        $tree->current = $currentPath;
        $tree->currentKey = $currentKey;
        $tree->currentName = null;
        $tree->currentRoute = null;

        return $tree;
    }
}

