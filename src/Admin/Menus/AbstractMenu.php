<?php

declare(strict_types=1);

namespace GraphQLAPI\GraphQLAPI\Admin\Menus;

use PoP\ComponentModel\Facades\Instances\InstanceManagerFacade;

/**
 * Admin menu class
 */
abstract class AbstractMenu
{
    protected $menuPageObjects;

    public function __construct()
    {
        $instanceManager = InstanceManagerFacade::getInstance();
        $this->menuPageObjects = array_map(
            function ($menuPageClass) use ($instanceManager) {
                return $instanceManager->getInstance($menuPageClass);
            },
            $this->getMenuPageClasses()
        );
    }

    abstract public static function getName();

    protected function getMenuPageClasses(): array
    {
        return [];
    }

    /**
     * Initialize the endpoints
     *
     * @return void
     */
    public function initialize(): void
    {
        /**
         * Low priority to execute before adding the menus for the CPTs
         */
        \add_action(
            'admin_menu',
            [$this, 'addMenuPagesTop'],
            9
        );
        /**
         * High priority to execute after adding the menus for the CPTs
         */
        \add_action(
            'admin_menu',
            [$this, 'addMenuPagesBottom'],
            20
        );

        /**
         * Initialize my menu pages
         *
         * @return void
         */
        foreach ($this->menuPageObjects as $menuPageObject) {
            $menuPageObject->initialize();
        }
    }
    public function addMenuPagesTop(): void
    {
        // Initially empty
    }

    public function addMenuPagesBottom(): void
    {
        // Initially empty
    }
}
