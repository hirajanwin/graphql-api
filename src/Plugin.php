<?php
namespace Leoloso\GraphQLByPoPWPPlugin;

use Leoloso\GraphQLByPoPWPPlugin\Admin\Menu;

class Plugin {

    public function init(): void
    {
        if (is_admin()) {
            (new Menu())->init();
            add_action(
                'admin_enqueue_scripts',
                [$this, 'enqueueAssets']
            );
        }
    }

    /**
     * Enqueue the required assets and initialize the localized scripts
     *
     * @return void
     */
    public function enqueueAssets(): void
    {
        $currentScreen = get_current_screen()->id;
		if (strpos($currentScreen, 'graphql_by_pop') !== false) {
            // CSS
			wp_enqueue_style(
                'graphql-by-pop-graphiql-client',
                GRAPHQL_BY_POP_PLUGIN_URL.'assets/css/graphiql-client.css',
                array(),
                GRAPHQL_BY_POP_VERSION
            );
			wp_enqueue_style(
                'graphql-by-pop-graphiql',
                GRAPHQL_BY_POP_PLUGIN_URL.'assets/css/vendors/graphiql.min.css',
                array(),
                GRAPHQL_BY_POP_VERSION
            );

            // JS: execute them all in the footer
			wp_enqueue_script(
                'graphql-by-pop-react',
                GRAPHQL_BY_POP_PLUGIN_URL.'assets/js/vendors/react.development.js',
                array(),
                GRAPHQL_BY_POP_VERSION,
                true
            );
			wp_enqueue_script(
                'graphql-by-pop-react-dom',
                GRAPHQL_BY_POP_PLUGIN_URL.'assets/js/vendors/react-dom.development.js',
                array('graphql-by-pop-react'),
                GRAPHQL_BY_POP_VERSION,
                true
            );
			wp_enqueue_script(
                'graphql-by-pop-graphiql',
                GRAPHQL_BY_POP_PLUGIN_URL.'assets/js/vendors/graphiql.min.js',
                array('graphql-by-pop-react-dom'),
                GRAPHQL_BY_POP_VERSION,
                true
            );
			wp_enqueue_script(
                'graphql-by-pop-graphiql-client',
                GRAPHQL_BY_POP_PLUGIN_URL.'assets/js/graphiql-client.js',
                array('graphql-by-pop-graphiql'),
                GRAPHQL_BY_POP_VERSION,
                true
            );

            $endpointURL = trailingslashit(trailingslashit(site_url()) . 'api/graphql');
            if (true) {
                $endpointURL = add_query_arg('use_namespace', true, $endpointURL);
            }

			wp_localize_script(
				'graphql-by-pop-graphiql-client',
				'graphQLByPoPGraphiQLSettings',
				array(
					'nonce' => wp_create_nonce('wp_rest'),
					'endpoint' => $endpointURL,
				)
			);
		}
    }
}
