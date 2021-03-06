<?php

declare(strict_types=1);

namespace GraphQLAPI\GraphQLAPI\Admin\Tables;

use GraphQLAPI\GraphQLAPI\General\RequestParams;
use GraphQLAPI\GraphQLAPI\Facades\ModuleRegistryFacade;
use GraphQLAPI\GraphQLAPI\Admin\MenuPages\ModulesMenuPage;
use GraphQLAPI\GraphQLAPI\Admin\MenuPages\SettingsMenuPage;
use PoP\ComponentModel\Facades\Instances\InstanceManagerFacade;
use GraphQLAPI\GraphQLAPI\Admin\TableActions\ModuleListTableAction;

/**
 * Module Table
 */
class ModuleListTable extends AbstractItemListTable
{
    /**
     * Singular name of the listed records
     *
     * @return string
     */
    public function getItemSingularName(): string
    {
        return \__('Module', 'graphql-api');
    }

    /**
     * Plural name of the listed records
     *
     * @return string
     */
    public function getItemPluralName(): string
    {
        return \__('Modules', 'graphql-api');
    }

    /**
     * Return all the items to display on the table
     *
     * @return array
     */
    public function getAllItems(): array
    {
        $items = [];
        $moduleRegistry = ModuleRegistryFacade::getInstance();
        $modules = $moduleRegistry->getAllModules();
        foreach ($modules as $module) {
            $moduleResolver = $moduleRegistry->getModuleResolver($module);
            $isEnabled = $moduleRegistry->isModuleEnabled($module);
            $items[] = [
                'module' => $module,
                'module-type' => $moduleResolver->getModuleType($module),
                'id' => $moduleResolver->getID($module),
                'is-enabled' => $isEnabled,
                'can-be-disabled' => $moduleResolver->canBeDisabled($module),
                'can-be-enabled' => !$isEnabled && $moduleRegistry->canModuleBeEnabled($module),
                'has-settings' => $moduleResolver->hasSettings($module),
                'name' => $moduleResolver->getName($module),
                'description' => $moduleResolver->getDescription($module),
                'depends-on' => $moduleResolver->getDependedModuleLists($module),
                // 'url' => $moduleResolver->getURL($module),
                'slug' => $moduleResolver->getSlug($module),
                'has-docs' => $moduleResolver->hasDocumentation($module),
            ];
        }
        return $items;
    }

    /**
     * List of item data
     *
     * @param int $per_page
     * @param int $page_number
     *
     * @return mixed
     */
    public function getItems($per_page = 5, $page_number = 1)
    {
        $results = $this->getAllItems();
        return array_splice(
            $results,
            ($page_number - 1) * $per_page,
            $per_page
        );
    }

    /**
     * Returns the count of records in the database.
     *
     * @return null|string
     */
    public function record_count()
    {
        $results = $this->getAllItems();
        return count($results);
    }

    /**
     * Render a column when no column specific method exist.
     *
     * @param array $item
     * @param string $column_name
     *
     * @return mixed
     */
    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'desc':
                $actions = [];
                // If it has, add a link to the documentation
                $instanceManager = InstanceManagerFacade::getInstance();
                $modulesMenuPage = $instanceManager->getInstance(ModulesMenuPage::class);
                if ($item['has-docs']) {
                    $url = \admin_url(sprintf(
                        'admin.php?page=%s&%s=%s&%s=%s&TB_iframe=true&width=600&height=550',
                        $modulesMenuPage->getScreenID(),
                        RequestParams::TAB,
                        RequestParams::TAB_DOCS,
                        RequestParams::MODULE,
                        urlencode($item['module'])
                    ));
                    $actions['docs'] = \sprintf(
                        '<a href="%s" class="%s" data-title="%s">%s</a>',
                        \esc_url($url),
                        'thickbox open-plugin-details-modal',
                        \esc_attr($item['name']),
                        \__('View details', 'graphql-api')
                    );
                }
                return sprintf(
                    '<div class="plugin-description"><p>%s</p></div><div class="second">%s</div>',
                    $item['description'],
                    $this->row_actions($actions, true)
                );
            case 'depends-on':
                // Output the list with AND lists of dependencies
                // Each list is an OR list of depended modules
                // It's formatted like this: module1, module2, ..., module5 or module6
                $items = [];
                $moduleRegistry = ModuleRegistryFacade::getInstance();
                $dependedModuleLists = $item[$column_name];
                if (!$dependedModuleLists) {
                    return \__('-', 'graphql-api');
                }
                /**
                 * This is a list of lists of modules, as to model both OR and AND conditions
                 */
                foreach ($dependedModuleLists as $dependedModuleList) {
                    if (!$dependedModuleList) {
                        continue;
                    }
                    $dependedModuleListNames = array_map(
                        function ($dependedModule) use ($moduleRegistry) {
                            $after = '';
                            // Check if it has the "inverse" token at the beginning,
                            // then it depends on the module being disabled, not enabled
                            if ($moduleRegistry->isInverseDependency($dependedModule)) {
                                // Revert to the normal module
                                $dependedModule = $moduleRegistry->getInverseDependency($dependedModule);
                                $after = \__('⇠ as disabled', 'graphql-api');
                            }
                            $moduleResolver = $moduleRegistry->getModuleResolver($dependedModule);
                            return sprintf(
                                '%1$s %2$s %3$s',
                                '▹',
                                $moduleResolver->getName($dependedModule),
                                $after
                            );
                        },
                        $dependedModuleList
                    );
                    if (count($dependedModuleListNames) >= 2) {
                        $lastElem = array_pop($dependedModuleListNames);
                        $commaElems = implode(
                            \__(', ', 'graphql-api'),
                            $dependedModuleListNames
                        );
                        $items[] = sprintf(
                            \__('%s or %s', 'graphql-api'),
                            $commaElems,
                            $lastElem
                        );
                    } else {
                        $items[] = $dependedModuleListNames[0];
                    }
                }
                return implode('<br/>', $items);
            case 'enabled':
                return \sprintf(
                    '<span role="img" aria-label="%s">%s</span>',
                    $item['is-enabled'] ? \__('Yes', 'graphql-api') : \__('No', 'graphql-api'),
                    $item['is-enabled'] ? '✅' : '❌'
                );
        }
        return '';
    }

    /**
     * Render the bulk edit checkbox
     *
     * @param array $item
     *
     * @return string
     */
    public function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="%s[]" value="%s" />',
            ModuleListTableAction::INPUT_BULK_ACTION_IDS,
            $item['id']
        );
    }

    /**
     * Method for name column
     *
     * @param array $item an array of DB data
     *
     * @return string
     */
    public function column_name($item)
    {
        $nonce = \wp_create_nonce( 'graphql_api_enable_or_disable_module' );
        $title = '<strong>' . $item['name'] . '</strong>';
        $linkPlaceholder = '<a href="?page=%s&action=%s&item=%s&_wpnonce=%s">%s</a>';
        $page = esc_attr($_REQUEST['page']);
        $actions = [];
        if ($item['is-enabled']) {
            // If it is enabled, offer to disable it
            // Unless the module cannot be disabled
            if ($item['can-be-disabled']) {
                $actions['disable'] = \sprintf(
                    $linkPlaceholder,
                    $page,
                    ModuleListTableAction::ACTION_DISABLE,
                    $item['id'],
                    $nonce,
                    \__('Disable', 'graphql-api')
                );
            } else {
                $actions['enabled'] = \__('Enabled', 'graphql-api');
            }

            // Maybe add settings links
            if ($item['has-settings']) {
                $instanceManager = InstanceManagerFacade::getInstance();
                $settingsMenuPage = $instanceManager->getInstance(SettingsMenuPage::class);
                $actions['settings'] = \sprintf(
                    '<a href="%s">%s</a>',
                    sprintf(
                        \admin_url(sprintf(
                            'admin.php?page=%s&tab=%s',
                            $settingsMenuPage->getScreenID(),
                            $item['id']
                        ))
                    ),
                    \__('Settings', 'graphql-api')
                );
            }
        } elseif ($item['can-be-enabled']) {
            // If not enabled and can be enabled, offer to do it
            $actions['enable'] = \sprintf(
                $linkPlaceholder,
                $page,
                ModuleListTableAction::ACTION_ENABLE,
                $item['id'],
                $nonce,
                \__('Enable', 'graphql-api')
            );
        } else {
            // Not enabled and can't be enabled, mention requirements not met
            // Not enabled for "striped" table style because, without a link, color contrast is not good:
            // gray font color over gray background
            // if ($this->usePluginTableStyle()) {
            $actions['disabled'] = \__('Disabled', 'graphql-api');
            // }
        }
        return $title . $this->row_actions($actions/*, $this->usePluginTableStyle()*/);
    }

    /**
     * Indicate if to show the enabled column or not
     *
     * @return array
     */
    protected function usePluginTableStyle(): bool
    {
        return true;
    }

    /**
     *  Associative array of columns
     *
     * @return array
     */
    public function get_columns()
    {
        return array_merge(
            [
                'cb' => '<input type="checkbox" />',
                'name' => \__('Module', 'graphql-api'),
            ],
            $this->usePluginTableStyle() ?
                [] :
                [
                    'enabled' => \__('Enabled', 'graphql-api'),
                ],
            [
                'desc' => \__('Description', 'graphql-api'),
                'depends-on' => \__('Depends on', 'graphql-api'),
            ]
        );
    }

    /**
     * Returns an associative array containing the bulk action
     *
     * @return array
     */
    public function get_bulk_actions()
    {
        return [
            ModuleListTableAction::ACTION_ENABLE => \__('Enable', 'graphql-api'),
            ModuleListTableAction::ACTION_DISABLE => \__('Disable', 'graphql-api'),
        ];
    }

    /**
     * Get a list of CSS classes for the WP_List_Table table tag.
     *
     * @since 3.1.0
     *
     * @return string[] Array of CSS classes for the table tag.
     */
    protected function get_table_classes()
    {
        // return array_merge(
        //     parent::get_table_classes(),
        //     [
        //         'plugins'
        //     ]
        // );
        if ($this->usePluginTableStyle()) {
            return array( 'widefat', 'plugins', $this->_args['plural'] );
        }
        return array_diff(
            parent::get_table_classes(),
            [
                'fixed'
            ]
        );
    }

    /**
     * Classnames to add to the row for the item
     *
     * @param object $item The current item
     */
    protected function getTableStyleRowClassnames($item): string
    {
        return sprintf(
            'module-%s',
            $item['module-type']
        );
    }

    /**
     * Generates content for a single row of the table
     *
     * @since 3.1.0
     *
     * @param object $item The current item
     */
    public function single_row($item)
    {
        if ($this->usePluginTableStyle()) {
            $classnames = sprintf(
                '%s %s',
                $this->getTableStyleRowClassnames($item),
                $item['is-enabled'] ? 'active' : 'inactive'
            );
            echo sprintf(
                '<tr class="%s">',
                $classnames
            );
            $this->single_row_columns($item);
            echo '</tr>';
        } else {
            parent::single_row($item);
        }
    }

    /**
     * Handles data query and filter, sorting, and pagination.
     */
    public function prepare_items()
    {
        $this->_column_headers = $this->get_column_info();

        /** Process bulk or single action */
        $instanceManager = InstanceManagerFacade::getInstance();
        $tableAction = $instanceManager->getInstance(ModuleListTableAction::class);
        $tableAction->maybeProcessAction();

        $per_page = $this->get_items_per_page(
            $this->getItemsPerPageOptionName(),
            $this->getDefaultItemsPerPage()
        );
        $current_page = $this->get_pagenum();
        $total_items  = $this->record_count();

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
        ]);

        $this->items = $this->getItems($per_page, $current_page);
    }

    /**
     * Enqueue the required assets
     *
     * @return void
     */
    public function enqueueAssets(): void
    {
        parent::enqueueAssets();

        /**
         * Fix the issues with the WP List Table
         */
        \wp_enqueue_style(
            'graphql-api-module-list-table',
            \GRAPHQL_API_URL . 'assets/css/module-list-table.css',
            array(),
            \GRAPHQL_API_VERSION
        );
    }

    /**
     * Customize the width of the columns
     */
    public function printStyles(): void
    {
        parent::printStyles();

        /*
        if ($this->usePluginTableStyle()) {
            ?>
            <style type="text/css">
                .wp-list-table .column-name { width: 25%; }
                .wp-list-table .column-description { width: 75%; }
            </style>
            <?php
        } else {
            ?>
            <style type="text/css">
                .wp-list-table .column-name { width: 25%; }
                .wp-list-table .column-enabled { width: 10%; }
                .wp-list-table .column-description { width: 65%; }
            </style>
            <?php
        }
        */
    }
}
