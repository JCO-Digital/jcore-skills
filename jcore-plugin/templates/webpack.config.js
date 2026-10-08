const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
{{#blocks}}
		// Blocks found under src/ by --blocks-manifest.
		...defaultConfig.entry(),
{{/blocks}}
		admin: './src/admin/index.js',
	},
};
